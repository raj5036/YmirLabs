<?php

namespace App\Http\Controllers;

use Auth;
use Log;

use App\User;
use App\Image;
use App\Examination;
use App\BugReport;
use App\Diagnosis;
use App\MedUser;
use App\PromoCode;
use App\PromoCodeLog;
use App\PartnerUserClaimNumber;
use App\Notification;
use App\Notifications\SmsSender;
use App\Feedback;
use \Stripe\Customer;
use \Stripe\Token;

use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use \Mailjet\Resources;
use \Aws\S3\S3Client;
use Carbon\Carbon;


/**
 * Frontend api controller used by website to register and check examinations.
 */
class ApiController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
    // Public functions mapped in web.php

    /**
     * Fetch User Details
     */
    public function user(Request $request)
    {
        $authUser = Auth::user();
        $user = User::findOrFail($authUser->uuid);
        if($user){
            return response()->json(['user' => $user], 200);
        } else {
            return response()->json(['error' => 'User not found'], 400);




        }

    }

    /**
     * Fetch Doctor images
     */
    public function doctors(Request $request)
    {
        $this->validate(
            $request, [
                'region' => 'required|in:co,no,se,uk,de',
            ]
        );

        $region = $request->region;

        $doctors = MedUser::select('display_image','display_name')
                ->where('servable_regions', 'LIKE', '%'.$region.'%')
                ->where('show_doctor', true)
                ->whereNotNull('display_image')->get();

        return response()->json(['doctors' => $doctors], 200);
    }

    /**
     * Fetch Doctor images
     */
    public function doctorView(Request $request, $uuid=null)
    {
        $doctor = MedUser::select('display_image','display_name', 'profession')
                ->where('uuid', '=', $uuid)
                ->get();

        return response()->json(['doctor' => $doctor], 200);
    }

    /**
     * Uploads images to backend.
     *
     * @param Request $request http request with file content.
     *
     * @return http response
     */
    public function upload(Request $request)
    {
        try{
            $this->validate(
                $request, [
                    'image' => 'required|image',
                ]
            );
        }catch(\Illuminate\Validation\ValidationException $e){
            return \response($e->errors(),400);
        }
       
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $file = $request->file('image');
            $image = Image::create(
                [
                    'type' => $file->getMimeType(),
                    'size' => $file->getClientSize(),
                    'suffix' => $file->guessExtension()
                ]
            );
            $imageName = $image->uuid . '.' . $image->suffix;
            $this->S3Upload($imageName, $file);
            return response()->json(['image' => $imageName]);
        }
        // Something went wrong with the fileupload
        return reponse()->json(['error' => 'upload.failed'], 500);
    }

    /**
     * This is called for partner users where `isCovered` is true, which means they should not have to pay
     * for the examination
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function confirmPartnerExamination(Request $request) {
        $this->validate(
            $request, [
                'examination' => 'required',
                'claim_uuid' => 'sometimes'
            ]
        );

        // Get the user that is making the request
        $authUser = Auth::user();
        $user = User::findOrFail($authUser->uuid);

        $examination = Examination::where('uuid', $request->input('examination'))->first();

        // If this is a partner user and we found the examination
        if ($user->partner && $examination) {
            
            if($user->partner === 'Aurora'){
                $variables = [
                    "name" => $user->firstname . ' ' . $user->lastname,
                    "email" => $user->email,
                    "created_time" => Carbon::parse($examination->created_at)->format('d/m/Y H:i')
                ];
                Log::info($variables);
                $email_response = $this->sendMailViaMailjet("support@snapmed.no", env('AURORA_SUPPORT_MAIL'), (int) env('MJ_AURORA_USER_TEMPLATE'), $variables);
                if ($email_response->success()) {
                    Log::info('Email sent to ' . env('AURORA_SUPPORT_MAIL'));
                }else{
                    Log::info('Email failed for ' . env('AURORA_SUPPORT_MAIL'));
                    Log::error(print_r($email_response));
                }
               }

            $claimId =  $request->input('claim_uuid');
            if ($claimId) {
                $claim = PartnerUserClaimNumber::findOrFail($claimId);
                if ($claim->user_id !== $user->uuid) {
                    return response()->json(['error' => 'login.failure'], 400);
                }
            }

            $dt            = new \DateTime( 'now' );
            $deadline      = 24; // always 24 hr deadline for partner users
            $deadline_time = $dt->modify( '+' . $deadline . ' hour' );
            $examination->deadline = $deadline;
            $examination->deadline_time = $deadline_time;
            $examination->payment_type  = Examination::PAYMENT_TYPE_PARTNER;
            if ($claimId) {
                $examination->partner_user_claim_number = $claimId;
            }
            $examination->save();
            Log::info('Partner user uuid ' . $user->uuid . ' confirmed examination: ' . $examination->uuid);
            return response()->json(['status' => 'ok']);
        } else {
            Log::info('Partner tried to confirm examination but failed - ' . $user ? $user->uuid : 'No user' . ', examinationId: '.$request->input('examination'));
            return response()->json(['error' => 'login.failure'], 400);
        }
    }

    /**
     * Submit checkup, user already logged in
     * // check jwt token / auth
     * // create and return examination
     *
     * @param Request $request
     */
    public function userSubmitCheck(Request $request) {
        $this->validate(
            $request, [
                'who' => 'required|in:me,other',
                'child_ssn' => 'sometimes',
                'age' => 'required|numeric',
                'gender' => 'required|in:man,woman,prefer_not',
                'pregnant' => 'sometimes|in:yes,no|nullable',
                'breastfeeding' => 'sometimes|in:yes,no|nullable',
                'date_of_birth' => 'required',
                'closeup' => 'sometimes',
                'overview' => 'sometimes',
                'description' => 'required',
                'duration' => 'required',
                'body_part' => 'required',
                'medication' => 'required|in:yes,no',
                'allergy' => 'required|in:yes,no',
                'medication_description' => 'sometimes',
                'allergy_description' => 'sometimes'
            ]
        );

        // Get the user that is making the request
        $authUser = Auth::user();
        $user = User::findOrFail($authUser->uuid);
        $user->date_of_birth = $request->input('date_of_birth');
        $user->gender = $request->input('gender');
        $user->save();

        $encryptedChildSsn = null;
        if ($request->input('child_ssn')) {
            $encryptedChildSsn = Crypt::encryptString($request->input('child_ssn'));
        }
        $examinationData = [
            'patient' => $user->uuid,
            'who' => $request->input('who'),
            'age' => $request->input('age'),
            'child_ssn' => $encryptedChildSsn,
            'pregnant' => $request->input('pregnant'),
            'breastfeeding' => $request->input('breastfeeding'),
            // We now say that all come in as other. This way the medical admin stays unchanged as well as
            // the datamodel that supports this.
            'category' => 'picture',
            'other_description' => json_encode($request->input('description')),
            'duration' => $request->input('duration'),
            'body_part' => json_encode($request->input('body_part')),
            'medication' => $request->input('medication'),
            'allergy' => $request->input('allergy'),
            'medication_description' => $request->input('medication_description'),
            'allergy_description' => $request->input('allergy_description')
        ];

        // Save content created by user.
        $examination = Examination::create($examinationData);

        if ($request->input('closeup')) {
            $examination->closeups()->attach($request->input('closeup'), ['type' => 'closeup']);
        }
        if ($request->input('overview')) {
            $examination->overviews()->attach($request->input('overview'), ['type' => 'overview']);
        }

        Log::info('Examination submitted: ' . $examination->uuid);
        return response()->json(
            [
                'examination' => $examination->uuid,
                'uuid' => $user->uuid
            ]
        );
    }

    public function checkExamination(Request $request) {
        $authUser = Auth::user();
        $user = User::findOrFail($authUser->uuid);

        $incompleteExamination = Examination::where('patient', '=', $user->uuid)
                                ->whereNull('charged')
                                ->whereNull('stripe')
                                ->orderBy('created_at', 'desc')
                                ->first();

        if($incompleteExamination) {
            if($incompleteExamination->closeups()->orderBy('created_at','desc')->first()){
                $closeupObj = $incompleteExamination->closeups()->orderBy('created_at','desc')->first();
                $closeup = $closeupObj->uuid.'.'.$closeupObj->suffix;
            } else {
                $closeup = null;
            }
    
            if($incompleteExamination->overviews()->orderBy('created_at','desc')->first()){
                $overviewObj = $incompleteExamination->overviews()->orderBy('created_at','desc')->first();
                $overview = $overviewObj->uuid.'.'.$overviewObj->suffix;
            } else {
                $overview = null;
            }
        }

        if($user->idProof()->orderBy('created_at','desc')->first()){
            $idproofObj = $user->idProof()->orderBy('created_at','desc')->first();
            $idproof = $idproofObj->uuid.'.'.$idproofObj->suffix;
        } else {
            $idproof = null;
        }

        if(Examination::where('patient', $user->uuid)->whereNotNull('charged')->whereNotNull('stripe')->count() >= 1) {
            $olderExamination = Examination::where('patient', $user->uuid)->orderBy('created_at', 'desc')->first();
            if($incompleteExamination){
                return response()->json(
                    [
                        'name' => $user->firstname . ' ' . $user->lastname,
                        'address' => $user->address,
                        'date_of_birth' => $user->date_of_birth,
                        'ethnicity' => $user->ethnicity,
                        'gender' => $user->gender,
                        'userPasswordAlreadySet' => strlen($user->password) > 2 ? true : false,
                        'examination' => $incompleteExamination->uuid,
                        'pregnant' => $incompleteExamination->pregnant=== null ? $olderExamination->pregnant : $incompleteExamination->pregnant,
                        'breastfeeding' => $incompleteExamination->breastfeeding=== null ? $olderExamination->breastfeeding : $incompleteExamination->breastfeeding,
                        'duration' => $incompleteExamination->duration,
                        'body_part' => $incompleteExamination->body_part,
                        'description' => $incompleteExamination->other_description,
                        'medication' => $incompleteExamination->medication === null ? $olderExamination->medication : $incompleteExamination->medication,
                        'medication_description' => $incompleteExamination->medication_description=== null ? $olderExamination->medication_description : $incompleteExamination->medication_description,
                        'allergy' => $incompleteExamination->allergy=== null ? $olderExamination->allergy : $incompleteExamination->allergy,
                        'allergy_description' => $incompleteExamination->allergy_description=== null ? $olderExamination->allergy_description : $incompleteExamination->allergy_description,
                        'family_history' => $incompleteExamination->family_history=== null ? $olderExamination->family_history : $incompleteExamination->family_history,
                        'family_history_description' => $incompleteExamination->family_history_description=== null ? $olderExamination->family_history_description : $incompleteExamination->family_history_description,
                        'treatment' => $incompleteExamination->treatment=== null ? $olderExamination->treatment : $incompleteExamination->treatment,
                        'treatment_description' => $incompleteExamination->treatment_description=== null ? $olderExamination->treatment_description : $incompleteExamination->treatment_description,
                        'closeup' =>  $closeup,
                        'overview' => $overview,
                        'idProof' => $idproof
                    ]
                );
            } else {
                return response()->json(
                    [
                        'name' => $user->firstname . ' ' . $user->lastname,
                        'address' => $user->address,
                        'date_of_birth' => $user->date_of_birth,
                        'ethnicity' => $user->ethnicity,
                        'gender' => $user->gender,
                        'userPasswordAlreadySet' => strlen($user->password) > 2 ? true : false,
                        'pregnant' => $olderExamination->pregnant,
                        'breastfeeding' => $olderExamination->breastfeeding,
                        'medication' => $olderExamination->medication,
                        'medication_description' =>$olderExamination->medication_description,
                        'allergy' => $olderExamination->allergy,
                        'allergy_description' => $olderExamination->allergy_description,
                        'family_history' => $olderExamination->family_history,
                        'family_history_description' => $olderExamination->family_history_description,
                        'treatment' => $olderExamination->treatment,
                        'treatment_description' => $olderExamination->treatment_description,
                        'idProof' => $idproof
                    ]
                );
            }
        } else {
            if($incompleteExamination) {
                return response()->json(
                    [
                        'name' => $user->firstname . ' ' . $user->lastname,
                        'address' => $user->address,
                        'date_of_birth' => $user->date_of_birth,
                        'ethnicity' => $user->ethnicity,
                        'gender' => $user->gender,
                        'userPasswordAlreadySet' => strlen($user->password) > 2 ? true : false,
                        'examination' => $incompleteExamination->uuid,
                        'pregnant' =>$incompleteExamination->pregnant,
                        'breastfeeding' => $incompleteExamination->breastfeeding,
                        'duration' => $incompleteExamination->duration,
                        'body_part' => $incompleteExamination->body_part,
                        'description' => $incompleteExamination->other_description,
                        'medication' => $incompleteExamination->medication,
                        'medication_description' => $incompleteExamination->medication_description,
                        'allergy' => $incompleteExamination->allergy,
                        'allergy_description' => $incompleteExamination->allergy_description,
                        'family_history' => $incompleteExamination->family_history,
                        'family_history_description' => $incompleteExamination->family_history_description,
                        'treatment' => $incompleteExamination->treatment,
                        'treatment_description' => $incompleteExamination->treatment_description,
                        'closeup' =>  $closeup,
                        'overview' => $overview,
                        'idProof' => $idproof
                    ]
                );
            } else {
                return response()->json(
                    [
                        'name' => $user->firstname . ' ' . $user->lastname,
                        'address' => $user->address,
                        'date_of_birth' => $user->date_of_birth,
                        'ethnicity' => $user->ethnicity,
                        'gender' => $user->gender
                    ]);
            }
        }
    }

    /**
     * Add additional user data, user already logged in UK flow
     * @param Request $request
     */
    public function userUpdate(Request $request) {
        $this->validate(
            $request, [
                'name' => 'required',
                'gender' => 'required|in:man,woman',
                'ethnicity' => 'required',
                'date_of_birth' => 'required',
                'pregnant' => 'sometimes|in:yes,no|nullable',
                'breastfeeding' => 'sometimes|in:yes,no|nullable',
                'examination' => 'sometimes'
            ]
        );

        // Get the user that is making the request
        $authUser = Auth::user();
        $user = User::findOrFail($authUser->uuid);

        $userName = explode(' ',$request->input('name'));
        $user->firstname = $userName[0];
        if(count($userName)>1) {
            $user->lastname = $userName[1];
        }

        $user->date_of_birth = $request->input('date_of_birth');
        $user->address = $request->input('address');
        $user->ethnicity = $request->input('ethnicity');
        $user->gender = $request->input('gender');
        $user->save();

        if($request->input('examination')){
            $examination = Examination::where('uuid', '=', $request->input('examination'))->first();
            $examination->breastfeeding = $request->input('breastfeeding');
            $examination->pregnant = $request->input('pregnant');
            $examination->save();
        }

        Log::info('User Basic details updated: ' . $user->uuid);
        return response()->json(
            [
                'uuid' => $user->uuid
            ]
        );
    }

    /**
     * Create a new examination or update
     * @param Request $request
     */
    public function examination(Request $request) {
        $this->validate(
            $request, [
                'pregnant' => 'sometimes|in:yes,no|nullable',
                'breastfeeding' => 'sometimes|in:yes,no|nullable',
                'duration' => 'required',
                'body_part' => 'required',
                'description' => 'required',
                'closeup' => 'sometimes',
                'overview' => 'sometimes',
            ]
        );

        // Get the user that is making the request
        $authUser = Auth::user();
        $user = User::findOrFail($authUser->uuid);

        $age = $request->input('age');

        $examinationData = [
            'patient' => $user->uuid,
            //By default 'me' for UK flow as minor flow is disabled
            'who' => 'me',
            'age' => $age,
            'pregnant' => $request->input('pregnant'),
            'breastfeeding' => $request->input('breastfeeding'),
            // We now say that all come in as other. This way the medical admin stays unchanged as well as
            // the datamodel that supports this.
            'category' => 'picture',
            'duration' => $request->input('duration'),
            'body_part' => json_encode($request->input('body_part')),
            'other_description' => $request->input('description'),
        ];

        $incompleteExamination = Examination::where('patient', '=', $user->uuid)
                                    ->whereNull('charged')
                                    ->whereNull('stripe')
                                    ->orderBy('created_at', 'desc')
                                    ->first();

        if($incompleteExamination) {
            $incompleteExamination->fill($examinationData);
            $incompleteExamination->save();

            Log::info('Examination updated: ' . $incompleteExamination->uuid);
            return response()->json(
                [
                    'examination' => $incompleteExamination->uuid
                ]
            );
        } else {
            $examination = Examination::create($examinationData);

            if (!$examination->closeups()->orderBy('created_at','desc')->first() && $request->input('closeup')) {
                $examination->closeups()->attach($request->input('closeup'), ['type' => 'closeup']);
            }
            if (!$examination->overviews()->orderBy('created_at','desc')->first() && $request->input('overview')) {
                $examination->overviews()->attach($request->input('overview'), ['type' => 'overview']);
            }

            Log::info('Examination submitted UK: ' . $examination->uuid);
            return response()->json(
                [
                    'examination' => $examination->uuid,
                    'uuid' => $user->uuid
                ]
            );
        }
    }

    /**
     * Update examination data
     * @param Request $request
     */
    public function examinationUpdate(Request $request) {
        $this->validate(
            $request, [
                'examination'=> 'required',
                'medication' => 'required|in:yes,no',
                'allergy' => 'required|in:yes,no',
                'medication_description' => 'sometimes',
                'allergy_description' => 'sometimes',
                'family_history' => 'required|in:yes,no',
                'family_history_description' => 'sometimes',
                'treatment' => 'required|in:yes,no',
                'treatment_description' => 'sometimes',
        ]);

        $examination = Examination::where('uuid', '=', $request->input('examination'))->first();

        if($examination){
            $examinationData = [
                'medication' => $request->input('medication'),
                'medication_description' => $request->input('medication_description'),
                'allergy' => $request->input('allergy'),
                'allergy_description' => $request->input('allergy_description'),
                'family_history' => $request->input('family_history'),
                'family_history_description' => $request->input('family_history_description'),
                'treatment' => $request->input('treatment'),
                'treatment_description' => $request->input('treatment_description'),
            ];
            $examination->fill($examinationData);
            $examination->save();

            Log::info('Examination updated UK: ' . $examination->uuid);
            return response()->json(
                [
                    'examination' => $examination->uuid
                ]
            );
        }
    }

    /**
     * Update user data
     * @param Request $request
     */
    public function userAdditionalData(Request $request) {
        $this->validate(
            $request, [
                'address' => 'required',
                'idProof' => 'required'
        ]);

        // Get the user that is making the request
        $authUser = Auth::user();
        $user = User::findOrFail($authUser->uuid);

        $user->address = $request->input('address');
        $user->save();

        if(!$user->idProof()->orderBy('created_at','desc')->first()){
            $user->idProof()->attach($request->input('idProof'), ['type' => 'idProof']);
        }

        return response()->json([]);
    }

    /**
     * Submit checkup, user already logged in UK flow
     * additional fields are added in the UK flow
     * // check jwt token / auth
     * // create and return examination
     *
     * @param Request $request
     */
    public function userSubmitCheckUK(Request $request) {
        $this->validate(
            $request, [
                'address' => 'required|in:yes,no',
                'idProof' => 'required'
            ]
        );

        // Get the user that is making the request
        $authUser = Auth::user();
        $user = User::findOrFail($authUser->uuid);

        //age 
        $age = $request->input('age');

        $examinationData = [
            'patient' => $user->uuid,
            //By default 'me' for UK flow as minor flow is disabled
            'who' => 'me',
            'gender' => $request->input('gender'),
            'age' => $age,
            'pregnant' => $request->input('pregnant'),
            'breastfeeding' => $request->input('breastfeeding'),
            // We now say that all come in as other. This way the medical admin stays unchanged as well as
            // the datamodel that supports this.
            'category' => 'picture',
            'duration' => $request->input('duration'),
            'body_part' => json_encode($request->input('body_part')),
            'other_description' => $request->input('description'),
            'medication' => $request->input('medication'),
            'medication_description' => $request->input('medication_description'),
            'allergy' => $request->input('allergy'),
            'allergy_description' => $request->input('allergy_description'),
            'family_history' => $request->input('family_history'),
            'family_history_description' => $request->input('family_history_description'),
            'treatment' => $request->input('treatment'),
            'treatment_description' => $request->input('treatment_description'),
            'affiliate_partner' => $request->input('affiliate_partner')
        ];

        // Save content created by user.
        $examination = Examination::create($examinationData);

        if ($request->input('closeup')) {
            $examination->closeups()->attach($request->input('closeup'), ['type' => 'closeup']);
        }
        if ($request->input('overview')) {
            $examination->overviews()->attach($request->input('overview'), ['type' => 'overview']);
        }
        if ($request->input('idProof')) {
            $examination->idProof()->attach($request->input('idProof'), ['type' => 'idProof']);
        }
        Log::info('Examination submitted UK: ' . $examination->uuid);
        return response()->json(
            [
                'examination' => $examination->uuid,
                'uuid' => $user->uuid
            ]
        );
    }

    /**
     * Submit your checkup content including phonenumber and password.
     *
     * @param Request $request http
     *
     * @return http response
     */
    public function check(Request $request)
    {
        $this->validate(
            $request, [
                'phone' => 'required',
                'password' => 'required',
                'locale' => 'required|in:nb,en,sv,de,gb',
                'region' => 'required|in:co,no,se,uk,de',
                'who' => 'required|in:me,other',
                'child_ssn' => 'sometimes',
                'gender' => 'required|in:man,woman',
                'pregnant' => 'sometimes|in:yes,no|nullable',
                'breastfeeding' => 'sometimes|in:yes,no|nullable',
                'age' => 'required|numeric',
                'closeup' => 'sometimes',
                'overview' => 'sometimes',
                // 'closeup' => 'sometimes|exists:images,uuid',
                // 'overview' => 'sometimes|exists:images,uuid',
                'description' => 'required',
                'medication' => 'required|in:yes,no',
                'allergy' => 'required|in:yes,no',
                'medication_description' => 'sometimes',
                'allergy_description' => 'sometimes'
            ]
        );

        $user = User::where(['phonenumber' => $request->input('phone'), 'login_method' => User::LOGIN_METHOD_PHONE])->first();
        Log::info('Examination submitted: Returning user? ' . ($user ? 'Yes' : 'No'));
        // Is there already a user registered with that phonenumber?
        if ($user) {
            // Yes, then attempt to login the user.
            if (!$this->attemptLogin($user, $request->input('password'))) {
                Log::info('Examination submitted: Login failed - wrong password provided.');
                // The login failed. Return error.
                return response()->json(['error' => 'login.failure'], 400);
            }
            $email = $user->email;
            $user->locale = $request->input('locale');
            $user->region = $request->input('region');
            $user->save();
        } else {
            // No, then lets create a user.
            $user = User::create(
                [
                    'phonenumber_not_verified' => $request->input('phone'),
                    'password' => app('hash')->make($request->input('password')),
                    'login_method' => User::LOGIN_METHOD_PHONE,
                    'locale' => $request->input('locale'),
                    'region' => $request->input('region'),
                    'gender' => $request->input('gender'),
                ]
            );
            $this->attemptLogin($user, $request->input('password'), $request->input('phone'));
            $email = false;
        }
        $encryptedChildSsn = null;
        if ($request->input('child_ssn')) {
            $encryptedChildSsn = Crypt::encryptString($request->input('child_ssn'));
        }
        // Save content created by user.
        $examination = Examination::create(
            [
                'patient' => $user->uuid,
                'who' => $request->input('who'),
                'child_ssn' => $encryptedChildSsn,
                'age' => $request->input('age'),
                'pregnant' => $request->input('pregnant'),
                'breastfeeding' => $request->input('breastfeeding'),
                // We now say that all come in as other. This way the medical admin stays unchanged as well as
                // the datamodel that supports this.
                'category' => 'picture',
                'other_description' => $request->input('description'),
                'medication' => $request->input('medication'),
                'allergy' => $request->input('allergy'),
                'medication_description' => $request->input('medication_description'),
                'allergy_description' => $request->input('allergy_description')
            ]
        );
        if ($request->input('closeup')) {
            $examination->closeups()->attach($request->input('closeup'), ['type' => 'closeup']);
        }
        if ($request->input('overview')) {
            $examination->overviews()->attach($request->input('overview'), ['type' => 'overview']);
        }

        Log::info('Examination submitted: ' . $examination->uuid);
        return response()->json(
            [
                'examination' => $examination->uuid,
                'token' => $this->jwt($user),
                'email' => $email,
                'uuid' => $user->uuid
            ]
        );
    }

    /**
     * Resubmit your checkup because you entered the wrong otp.
     *
     * @param Request $request http
     *
     * @return http response
     */
    public function recheck(Request $request)
    {
        $this->validate(
            $request, [
                'phonenumber' => 'required',
                'password' => 'required',
                'examination' => 'required',
            ]
        );
        $user = User::where(function ($query) use ($request) {
            $query->where('phonenumber', '=', $request->input('phonenumber'))
                    ->orWhere('phonenumber_not_verified', '=', $request->input('phonenumber'));
            })->first();
        $examination = Examination::findOrFail($request->input('examination'));

        // Is there already a user registered with that phonenumber?
        if ($user) {
            // Yes, then attempt to login the user.
            if (!$this->attemptLogin($user, $request->input('password'))) {
                // The login failed. Return error.
                return response()->json(['error' => 'login.failure1'], 400);
            }
            $email = $user->email;
        } else {
            // The login failed. Return error.
            return response()->json(['error' => 'login.failure2'], 400);
        }

        return response()->json(
            [
                'examination' => $examination->uuid,
                'token' => $this->jwt($user),
                'email' => $email,
                'uuid' => $user->uuid
            ]
        );
    }

    /**
     * Get promo code for auto apply feature
     * 
     * @param Request $request http request with country currency
     * 
     * @return http response with promocode
     */
    public function getpromo(Request $request)
    {
        $this->validate(
            $request, [
                'currency' => 'required|in:NOK,EUR,USD,SEK,GBP'
            ]
        );

        $currency = $request->currency;

        $promoCode = PromoCode::where('auto_apply', true)
        ->where('valid_from', '<', new \DateTime('now'))
        ->where('valid_to', '>', new \DateTime('now'))
        ->where(function($q) use($currency) {
            $q->where('applicable_currencies', 'LIKE', '%'.$currency.'%')
              ->orWhere('applicable_currencies', 'LIKE', '%ALL%');
        })->orderBy('updated_at')->first();

        if($promoCode) {
            return response()->json($promoCode->promocode, 200);
        } else {
            return response()->json('', 200);
        }

    }

    /**
     * Check the promo code entered by the user and return discount percentage if its valid otherwise return if the code is valid/used.
     *
     * @param Request $request http request with promocode.
     *
     * @return http response with discount percent/validity/if used.
     */
    public function checkpromo(Request $request)
    {
        $this->validate(
            $request, [
                'promoCode' => 'required',
                'type' => 'required',
                'currency' => 'required|in:NOK,EUR,USD,SEK,GBP'
            ]
        );

        // To maintain consistency, converting the promocode to upper case.
        $promoCode = strtoupper($request->promoCode);
        // 'cs' for chat service and 'vs' for video service
        $type = $request->type;
        $user = Auth::user();
        $currency = $request->currency;

        return response()->json($this->promoCodeDetails($promoCode, $type, $user, $currency), 200);
        // Something went wrong with the fileupload
        // return reponse()->json(['error' => 'promo.failed'], 500);
    }

    /**
     * Charge the user for the examination they just ordered. Will first create a customer in stripe, and then execute
     * the charge for the examination.
     *
     * @param Request $request http request includes examination, stripe token and deadline time.
     *
     * @return http response with status of how this went.
     */
    public function charge(Request $request)
    {
        $stripe_error = null;
        $error_message = null;

        $this->validate(
            $request,
            [
                'examination' => 'required',
                'deadline' => 'required|numeric|in:12,24',
                'amountKey' => 'required',
                'amount' => 'required|numeric',
                'promoCode' => 'nullable|string',
                'currency' => 'required|in:NOK,EUR,USD,SEK,GBP',
                'payment_method_id' => 'required'
            ]
        );

        $currency = $request->input('currency');
        $deadline = $request->input('deadline');
        $tmpAmount = $request->input('amount');
        $promoCode = $request->input('promoCode');
        $amountKey = $request->input('amountKey');

        // Does the currency / amount map correctly with what is registred here?

        $amount = config('constants.currency_map.' . $currency . '.' . $amountKey);

        // Get the user that is making the request
        $user = Auth::user();

        if ($promoCode){
            // To maintain consistency, converting the promocode to upper case.
            Log::info('Promo code used: ' . $promoCode);
            $promoCode = strtoupper($promoCode);
            $promoData = $this->recalculateAmountWithPromo($amount, $promoCode, $deadline, $user, $currency);
            $amount = $promoData['amount'];

            // This will help us add log later
            if ($promoData['promoApplied']){
                $promoApplied = true;
            }
            else{
                $promoApplied = false;
            }
        }
        else{
            $promoApplied = false;
        }

        if ($amount != $tmpAmount) {
            $errorMessage = 'Client amount [' . $tmpAmount . '] does not match servers: ' . $amount;
            if (env('APP_ENV') != 'local' && app()->bound('sentry')) {
                app('sentry')->captureMessage($errorMessage);
                // No, then return an error to the user.
                return response()->json([
                    'error' => 'validation.exception',
                    'error.message' => 'Not correctly formatted content sent to server',
                ], 400);
            }
            Log::error('There was an error handling the request: ' . $errorMessage);
        }
        // Yes, the currency / amount validates so lets continue processing.

        // Amounts in stripe is handled as integers (whole numbers) so multiply by 100
        $amount = $amount * 100;

        // Get the examination that they are trying to pay for
        $examination = Examination::findOrFail($request->input('examination'));
        // Is the user already a stripe customer?
        if (!isset($user->stripe)) {
            // No, then create a customer in stripe.
            $customer = Customer::create(
                [
                    'description' => $user->phonenumber,
                ]
            );
            $user->stripe = $customer->id;
            $user->save();
        } else {
            // Yes, then retrieve the customer and set the new card as the source for payment.
            $customer = Customer::retrieve($user->stripe);
        }

        $payment_method_id =  $request->input('payment_method_id');
        $payment_method = \Stripe\PaymentMethod::retrieve($payment_method_id);
        $payment_method->attach(['customer' => $customer->id]);

        $examId = implode('-', str_split(substr($examination->uuid, -12), 4));

        $payment = array(
            'payment_method' => $payment_method_id,
            'payment_method_types' => ['card'],
            'amount' => $amount,
            'currency' => $currency,
            'customer' => $customer->id,
            'description' => 'Charged ['. $user->phonenumber . '] for exam: ' . $examId,
            'confirmation_method' => 'manual',
            'confirm' => true,
            'setup_future_usage' => 'off_session',
        );

        $intent = \Stripe\PaymentIntent::create($payment);
        Log::info('Appointment payment intent created: ' . json_encode($intent));

        // just add promocode to log immediately (previously this was done after payment)
        if ($promoApplied){
            $this->addPromoCodeLog($promoData, $user, $promoCode, $currency, $deadline);
        }

        if (($intent->status == 'requires_action' || $intent->status == 'requires_source_action') &&
            $intent->next_action->type == 'use_stripe_sdk') {

            // save intent id so we can find it later in the webhook
            $examination->stripe = $intent->id;
            $examination->payment_type = Examination::PAYMENT_TYPE_CARD;
            $examination->amount_paid = $tmpAmount;
            $examination->save();

            
            // Tell the client to handle the action
            return response()->json([
                'requires_action' => true,
                'payment_intent_client_secret' => $intent->client_secret,
                'id' => $intent->id,
                'description' => $payment['description']]);

        } else if ($intent->status == 'succeeded') {
            # The payment didn’t need any additional actions and completed!
            # Handle post-payment fulfillment

            // The charge went through then update the examination with payment information and respond to user.
            $dt = new \DateTime('now');
            $examination->deadline = $deadline;
            $examination->deadline_time = $dt->modify('+'. $deadline .' hour');
            $examination->charged = new \DateTime('now');
            $examination->stripe = $intent->id;
            $examination->payment_type = Examination::PAYMENT_TYPE_CARD;
            $examination->amount_paid = $tmpAmount;
            $examination->save();

            Log::info('Examination charged: ' . $examination->uuid . ' with exam id: ' . $examId);
            $this->sendReceiptToUser($user->phonenumber, $amount, $currency, time(), $examId, $user->locale, $user->region);

            return response()->json([
                'status' => 'charged',
                'success' => true,
                'id' => $intent->id,
                'description' => $payment['description']
            ]);
        } else {
            # Invalid status
            return response()->json(['error' => $intent], 400);
        }
    }

    /**
     * get list of cases for a user
     * @param Request $request
     */
    public function cases(Request $request)
    {
            $user = Auth::user();
            if ($user) {
                $sub = 

                 // Get the user that is making the request
                // Get the examination that they are trying to pay for
                $examinations = Examination::select('uuid', 'charged', 'category', 'diagnosed', 'created_at')
                ->where('patient', $user->uuid)
                ->where(function($query) {
                    $query->where('payment_type', Examination::PAYMENT_TYPE_PARTNER);
                    $query->orWhere(function($orQuery) {
                        $orQuery->where('payment_type', Examination::PAYMENT_TYPE_CARD);
                        $orQuery->whereNotNull('charged');
                        $orQuery->whereNotNull('stripe');
                    });
                })
                ->with('overviews')
                ->orderBy('updated_at', 'desc')
                ->get();

                foreach ($examinations as $examination) {
                    $doctor = MEDUSER::select('display_name')
                    ->where('uuid',(function ($query) use ($examination) {
                        $query->from('diagnoses')
                            ->select('performed_by')
                            
                            ->where('examination',(function ($query) use ($examination) {
                                $query->from('examinations')
                                    ->select('uuid')
                                    ->where('uuid', '=', $examination->uuid);
                            }))
                            ->orderBy('created_at','desc')
                            ->first();
                    }))
                    ->get();
                    if ($doctor) {
                        $examination['doctor'] = $doctor;
                    } else {
                        $examination['doctor'] = null;
                    }
                }

                return response()->json(['examinations' => $examinations]);
            } else {
                return response()->json(['examinations' => '']);
            }
    }

    /**
     * get a case from uuid
     * @param Request $request
     */
    public function casesView(Request $request, $uuid=null) {
        // Get the user that is making the request
        $authUser = Auth::user();
        $user = User::findOrFail($authUser->uuid);

        if($user){
            $examination = Examination::where('uuid', '=', $uuid)
                ->with(['diagnoses' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }, 'closeups', 'overviews', 'referrals'])
                ->orderBy('updated_at', 'desc')
                ->first();
            return response()->json($examination);
        } else {
            return response()->json(['error' => 'User not found'], 400);
        }
    }


    /**
     * Submit new images to be used instead of the old ones.
     *
     * @param Request $request http
     *
     * @return http response
     */
    public function images(Request $request)
    {
        $this->validate(
            $request, [
                'uuid' => 'required',
                'closeup' => 'required',
                'overview' => 'required',
            ]
        );

        // Get the user that is making the request
        $user = Auth::user();

        $examination = Examination::findOrFail($request->input('uuid'));

        // Is the currently authenticated user the same as the patient of the examination?
        if ($examination->patient === $user->uuid) {
            // Yes, then attach the new images and complete request
            $examination->closeups()->attach($request->input('closeup'), ['type' => 'closeup']);
            $examination->overviews()->attach($request->input('overview'), ['type' => 'overview']);
            $dt = new \DateTime('now');
            $examination->deadline_time = $dt->modify('+'. $examination->deadline .' hour');
            $examination->complete = true;
            // notify meduser that case has been updated
            if ($examination->rejected_by) {
                $this->sendImageUpdateToMeduser($examination->rejected_by);
                $examination->rejected_by = null;
                $examination->reject_reason = null;
            }
            $examination->save();
            return response()->json(['examination' => $examination]);
        }

        Log::warning("User [$user->uuid] is trying to update examination: [$examination->uuid] for patient: $examination->patient");
        // No, it is not the same user.
        return response()->json(['error' => 'forbidden'], 403);
    }

    public function second(Request $request)
    {
        $this->validate($request, ['uuid' => 'required', 'diagnosis' => 'required', 'reason' => 'required']);

        // Get the user that is making the request
        $user = Auth::user();

        $examination = Examination::findOrFail($request->input('uuid'));
        $diagnosis = Diagnosis::findOrFail($request->input('diagnosis'));

        // Is the currently authenticated user the same as the patient of the examination?
        if ($examination->patient === $user->uuid) {
            $dt = new \DateTime('now');
            $examination->deadline_time = $dt->modify('+'. $examination->deadline .' hour');
            $examination->diagnosed = false;
            $examination->save();

            $diagnosis->second_opinion_who = $user->uuid;
            $diagnosis->second_opinion_reason = $request->input('reason');
            $diagnosis->save();

            Log::info('Examination updated: ' . $examination->uuid . ' - requires second opinion.');

            return response()->json(['examination' => $examination]);
        }

        Log::warning("User [$user->uuid] is trying to update examination: [$examination->uuid] for patient: $examination->patient");
        // No, it is not the same user.
        return response()->json(['error' => 'forbidden'], 403);
    }

    public function question_confirm_payment(Request $request)
    {
        try {
            $intent = \Stripe\PaymentIntent::retrieve($request->input('payment_intent_id'));
            $response = $intent->confirm(

            );

            Log::info('question_confirm_payment $response=');
            Log::info($response);

            // Payment complete
            if($response->status === 'succeeded') {
                // Send user receipt etc. here
            }

            return response()->json(['status' => $response->status], 200);

        } catch(Exception $ex) {
            Log::error($ex);
            var_dump($ex);
        }
    }

    /**
     * Submit checkup for oslo users, user already logged in
     *
     * @param Request $request containing image uuid
     * @return http response with case code
     */
    public function submitImageCase(Request $request)
    {
        $this->validate(
            $request,
            [
                'closeup' => 'required',
                'overview' => 'required'
            ]
        );

        $authUser = Auth::user();
        $user = User::findOrFail($authUser->uuid);

        $caseCode = $this->generateRandomAlphaNum(6);
        $dt = new \DateTime('now');
        $deadline = 24;
        $deadlineTime = $dt->modify('+' . $deadline . ' hour');

        $examinationData = [
            'patient' => $user->uuid,
            'who' => 'other',
            'category' => 'picture',
            'other_description' => $caseCode,
            'deadline' => $deadline,
            'deadline_time' => $deadlineTime,
            'payment_type' => Examination::PAYMENT_TYPE_PARTNER,
            'case_code' => $caseCode
        ];

        $examination = Examination::create($examinationData);

        $examination->closeups()->attach($request->input('closeup'), ['type' => 'closeup']);
        $examination->overviews()->attach($request->input('overview'), ['type' => 'overview']);

        try {
            $message = "Bilder er sendt inn. \n\nKoden for henvisning er: $caseCode";
            SmsSender::sendSms($user->phonenumber, $message, $user->region);
        } catch (Exception $ex) {
            Log::error($ex);
            if (env('APP_ENV') != 'local' && app()->bound('sentry')) {
                app('sentry')->captureMessage($ex);
            }
        }

        Log::info('Examination submitted: ' . $examination->uuid);
        return response()->json(
            [
                'caseCode' => $caseCode
            ]
        );
    }

    public function feedback(Request $request){
        $this->validate(
            $request,
            [
                'feedback' => 'required',
                'user' => 'sometimes|nullable'
            ]
        );

        $feedbackData = ['feedback' => $request->input('feedback'), 'submittedBy' => $request->input('user')]; 

        $feedback = Feedback::create($feedbackData);
        return response()->json(['status' => 'Submitted'], 200);
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
    // Internal helper functions

    private function sendReceiptToUser($phonenumber, $charge_amount, $charge_currency, $charge_created, $examId, $lang = 'nb', $region)
    {
        try {
            $amount = ($charge_amount / 100);
            if ($lang !== 'nb' && $lang !== 'sv') {
                $amount = $amount . ' ' . $charge_currency;
            }
            $date = date('Y.m.d H:i:s', $charge_created);
            $messages = array(
                'nb' => "Takk for din bestilling.\n\nDu vil få svar når saken er vurdert.\n\nBeløp betalt: ${amount}kr\nTid: ${date}\nSjekk ID # ${examId}\n\n,\nDr. Dropin Hud",
                'sv' => "Tack för din beställning.\n\nVi meddelar dig så snart som ditt ärende har blivit bedömt.\n\nBetalt belopp: ${amount}kr\nTid: ${date}\nÄrende ID # ${examId}\n\nVänliga hälsningar,\nDr. Dropin Hud",
            );
            SmsSender::sendSms($phonenumber, $messages[$lang], $region);
        } catch (Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }

    private function sendImageUpdateToMeduser($uuid)
    {
        try {
            $user = MedUser::where('uuid', '=', $uuid)->first();
            $prefix = env('APP_ENV') === 'production' ? 'Snapmed:' : 'TEST Snapmed:';
            $message = "$prefix case has been updated with new images.";
            SmsSender::sendSms($user->phonenumber, $message, $user->country);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    private function generateRandomAlphaNum($size)
    {
        $permittedChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $inputLength = strlen($permittedChars);
        $randomString = '';
        for ($i = 0; $i < $size; $i++) {
            $random_character = $permittedChars[mt_rand(0, $inputLength - 1)];
            $randomString .= $random_character;
        }

        return $randomString;
    }

    /**
     * Private helper function to get a new bankId Guzzle http client
     *
     * @return \GuzzleHttp\Client
     */
    private function getBankIdClient()
    {
        $token = base64_encode(env('BANKID_USER') . ':' . env('BANKID_PASSWORD'));
        $client = new \GuzzleHttp\Client(
            [
                'base_uri' => env('BANKID_ENDPOINT'),
                'headers' => [
                    'Authorization' => 'Basic ' . $token,
                    'Content-Type'  => 'application/json'
                ]
            ]
        );

        return $client;
    }

    private function S3Upload($imageName, $file)
    {
        $s3Client = new S3Client([
            'region'  => 'eu-central-1',
            'version' => 'latest',
            'credentials' => [
                'key'    => env('S3_ACCES_KEY'),
                'secret' => env('S3_ACCESS_SECRET'),
                ]
            ]);		
        
        $s3Client->putObject([
            'Bucket' => env('S3_BUCKET_NAME'),
            'Key'    => "uploads/".$imageName,
            'SourceFile' => $file			
        ]);
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
}
