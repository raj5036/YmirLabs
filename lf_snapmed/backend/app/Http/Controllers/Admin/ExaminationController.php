<?php

namespace App\Http\Controllers\Admin;

use Auth;
use Illuminate\Support\Facades\Crypt;
use Log;

use App\User;
use App\MedUser;
use App\Diagnosis;
use App\Examination;
use App\Exports\ExaminationsExport;
use App\Interesting;
use Laravel\Lumen\Routing\Controller as BaseController;
use App\Notifications\SmsSender;
use App\Referral;
use Firebase\JWT\JWT;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use \Aws\S3\S3Client;

// TODO: change hardcoded links in this file

use \Mailjet\Resources;
class ExaminationController extends BaseController
{
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
    const COUNTRY_CURENCY_MAP = array(
        'no' => 'NOK',
        'se' => 'SEK',
        'de' => 'EUR',
        'uk' => 'GBP'
    );

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //

    /**
     * Log in the user to our system. Used only for users that already exist
     * in our system.
     *
     * @param Request $request http request with phonenumber and password.
     *
     * @return http response with jwt token.
     */
    public function login(Request $request)
    {

        if ($request->input('phonenumber') !== '') {
            // Delay response for 500ms to 1000ms to deter brute force attempts
            usleep((rand(500, 1000)) * 1000);
            // No, then return login error.
            return response()->json(['error' => 'login.failure'], 400);
        }

        $this->validate(
            $request, [
                'email' => 'required',
                'password' => 'required',
            ]
        );

        $user = MedUser::where('email', $request->input('email'))->first();
        // Is there a user already registered in our system?
        if (!isset($user) || $user === null) {
            // Delay response for 250ms to deter brute force attempts
            usleep(250 * 1000);
            // No, then return login error.
            return response()->json(['error' => 'login.failure'], 400);
        }
        // Yes, then attempt to login the user.
        if (!$this->attemptLogin($user, $request->input('password'))) {
            // Delay response for 100ms to deter brute force attempts
            usleep(100 * 1000);
            // The login failed. Return error.
            return response()->json(['error' => 'login.failure'], 400);
        }
        // The user was logged into our system, return JWT token.
        return response()->json(['token' => $this->jwt($user), 'refreshToken' => $this->jwtRefreshToken($user), 'uuid' => $user->uuid, 'superadmin' => (boolean) $user->superadmin]);
    }

    /**
     * Send otp to user who wants to reset password
     *
     * @param Request $request http request with phonenumber
     *
     * @return http response with jwt token.
     */
    public function resetPasswordSendOtp(Request $request)
    {
        $this->validate(
            $request,
            [
                'phonenumber' => 'required'
            ]
        );

        $user = MedUser::where('phonenumber', $request->input('phonenumber'))->first();
        if ($user) {
            $otp = $this->getToken(4);
            $this->sendOtpToUser($user->phonenumber, $otp, 'en', $user->country);
            // Encrypt the password and save it.
            $user->otp = app('hash')->make($otp);
            $user->save();
            return response()->json(['token' => $this->jwt($user), 'refreshToken' => $this->jwtRefreshToken($user), 'uuid' => $user->uuid, 'superadmin' => (bool) $user->superadmin, 'status'=> true], 200);
        } else {
            return response()->json([
                'status' => false,
                'code' => 'not_found'
            ], 200);
        }
    }

    /**
     * Change password of logged in user
     *
     * @param Request $request http request with old and new password
     *
     * @return http response with status code
     */
    public function changePassword(Request $request)
    {
        $this->validate(
            $request,
            [
                'current_password' => 'required',
                'new_password' => 'required|string|min:6'
            ]
        );

        if (!(Hash::check($request->input('current_password'), Auth::user()->password))) {
            return response()->json([
                'status' => false,
                'code' => 'match_err'
            ], 200);
        }

        if (strcmp($request->input('current_password'), $request->input('new_password')) == 0) {
            return response()->json([
                'status' => false,
                'code' => 'same_err'
            ], 200);
        }

        $user = Auth::user();
        $user->password = app('hash')->make($request->input('new_password'));
        $user->save();

        Log::info('Password changed for user: ' . $user->uuid);

        return response()->json([
            'status' => true,
            'code' => 'success'
        ], 200);
    }

    /**
     * Update the password of the user
     *
     * @param Request $request http request with token and password
     *
     * @return http response with status code
     */
    public function updatePassword(Request $request)
    {
        $this->validate(
            $request,
            [
                'token' => 'required|string',
                'password' => 'required|string|min:6'
            ]
        );

        $user = MedUser::where(['token' => $request->input('token')])->first();

        if (!$user) {
            return response()->json(['status' => false, 'code' => 'expire'], 200);
        }

        $user->is_email_verified = true;
        $user->token = null;
        $user->password = app('hash')->make($request->input('password'));
        $user->save();

        Log::info('Password updated for user: ' . $user->uuid);

        return response()->json(['status' => true, 'code' => 'updated'], 200);
    }

    /**
     * The one-time-password is handled by the authenticate middleware, therefor it is here just removed from db.
     *
     * @param String $otp one-time-password
     *
     * @return http response
     */
    public function verify(Request $request, $otp)
    {
        // Since we actually get into this method the token has been verified.
        $user = $request->user();
        // Lets update the user by removing the otp code, since they are now verified.
        $user->otp = null;
        $user->otp_failed_count = 0; // reset OTP failed count
        $user->save();

        // Does this request include a passwordReset for the user?
        if ($request->input('passwordReset')) {
            // Check if users mail exists.
            if ($user->email) {
                // if yes then send email with password reset url
                $token = uniqid();
                $variables = [
                    "redirect_url" => config('constants.doctor_password_reset_url.' . env('APP_ENV')) . "/" . $token
                ];

                $response = $this->sendMailViaMailjet("support@snapmed.no", $user->email, (int) env('MJ_RESET_PASSWORD'), $variables);

                if (!$response->success()) {
                    return response()->json(['status' => 'email_send_failed']);
                }
                $user->token = $token;
                $user->save();

                return response()->json(['status' => 'email_sent']);
            }
            // send notification to admin
            return response()->json(['status' => 'email_absent']);
        }
        return response()->json(['status' => 'ok']);
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Current authenticated user
        $physician = $request->user()->uuid;

        $examinations = Examination::where('complete', '=', true)
            ->where(function($query) {
                $query->where('payment_type', Examination::PAYMENT_TYPE_PARTNER);
                $query->orWhere(function($orQuery) {
                    $orQuery->where('payment_type', Examination::PAYMENT_TYPE_CARD);
                    $orQuery->whereNotNull('charged');
                    $orQuery->whereNotNull('stripe');
                });
            })
            ->where('diagnosed', '=', false)
            ->orderBy('deadline_time', 'asc')
            ->get();
        return response()->json($examinations);
    }

    public function stats (Request $request) {
        $physician = $request->user()->uuid;
        $servableRegions = MedUser::where('uuid', '=', $physician)->first()->servable_regions;

        $available = Examination::where('complete', '=', true)
            ->where(function($query) {
                $query->where('payment_type', Examination::PAYMENT_TYPE_PARTNER);
                $query->orWhere(function($orQuery) {
                    $orQuery->where('payment_type', Examination::PAYMENT_TYPE_CARD);
                    $orQuery->whereNotNull('charged');
                    $orQuery->whereNotNull('stripe');
                });
            })
            ->where('diagnosed', '=', false)
            ->where('category', '!=', 'video')
            ->where(
                function ($query) use ($physician) {
                    $query->where('locked_by', '=', $physician)
                        ->orWhereNull('locked_by');
                }
            )
            ->whereHas('client', function($query) use ($servableRegions) {
                $query->whereIn('region', json_decode($servableRegions));
            })
            ->whereNotIn('uuid', function ($query) use ($physician) {
                $query->select('examination')
                    ->where('performed_by', '!=', $physician)
                    ->from('diagnoses');
            })
            ->whereHas('client', function ($query) {
                $query->whereNotIn('partner', ['Oslo'])
                    ->orWhereNull('partner');
            })
            ->count();

        $dailyResults = DB::table('examinations')
                ->select(DB::Raw('DATE(updated_at) date'), 'category', DB::Raw('count(uuid) total'), DB::Raw('sum(diagnosed) completed'))
                ->where('complete', '=', true)
                ->where(function($query) {
                    $query->where('payment_type', Examination::PAYMENT_TYPE_PARTNER);
                    $query->orWhere(function($orQuery) {
                        $orQuery->where('payment_type', Examination::PAYMENT_TYPE_CARD);
                        $orQuery->whereNotNull('charged');
                        $orQuery->whereNotNull('stripe');
                    });
                })
                ->groupBy('date', 'category')
                ->orderBy('updated_at', 'category')
                ->get();

        $personalResults = DB::table('examinations')
            ->select('examinations.category', DB::Raw('count(examinations.uuid) total'), DB::Raw('sum(examinations.diagnosed) completed'))
            ->join('diagnoses', 'diagnoses.examination', '=', 'examinations.uuid')
            ->where('diagnoses.performed_by', '=', $physician)
            ->where(function($query) {
                $query->where('payment_type', Examination::PAYMENT_TYPE_PARTNER);
                $query->orWhere(function($orQuery) {
                    $orQuery->where('payment_type', Examination::PAYMENT_TYPE_CARD);
                    $orQuery->whereNotNull('charged');
                    $orQuery->whereNotNull('stripe');
                });
            })
            ->groupBy('examinations.category')
            ->orderBy('examinations.category')
            ->get();

        return response()->json([
            'available' => $available,
            'daily' => $dailyResults,
            'personal' => $personalResults
        ]);

    }

    /**
     * Search patient either by ssn or phonenumber.
     *
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $this->validate(
            $request,
            [
                'search' => 'required',
            ]
        );

        $searchTerm = $request->search;
        if (env('APP_ENV') === 'local' || env('APP_ENV') === 'test') {
            $searchTerm = env('BANKID_TEST_SSN');
        }
        $ssn_hash = sha1($searchTerm);
        $physician = $request->user()->uuid;
        $servableRegions = MedUser::where('uuid', '=', $physician)->first()->servable_regions;

        $user = User::where(function ($query) use ($ssn_hash, $servableRegions) {
            $query->where('ssn_hash', $ssn_hash)
                ->WhereIn('region', json_decode($servableRegions));
        })->orWhere(function ($query) use ($searchTerm, $servableRegions) {
            $query->whereRaw("REPLACE(phonenumber, ' ', '') = ?", [$searchTerm])
                ->WhereIn('region', json_decode($servableRegions));
        })->first();

        if ($user) {
            return response()->json(['client' => [
                'uuid' => $user->uuid,
                'ssn' => isset($user->ssn) ? Crypt::decryptString($user->ssn) : null
            ]]);
        } else {
            return response()->json(false);
        }
    }

    /**
     * Search Examination by case_code.
     *
     * @return \Illuminate\Http\Response
     */
    public function searchByCase(Request $request, $code)
    {
        $authUser = Auth::user();
        $case = Examination::where('case_code', '=', $code)
            ->with(['client', 'diagnoses', 'closeups', 'overviews', 'locked'])
            ->first();

        if ($case) {
            $return_cases = [];
            $can_edit = $case->locked == null || $case->locked->uuid === $authUser->uuid;
            $case['can_edit'] = $can_edit;

            // add icd_codes since it is hidden
            $diagnoses_arr = [];
            foreach ($case['diagnoses'] as $diagnose) {
                $diagnose->makeVisible('icd_codes');
                $diagnoses_arr[] = $diagnose;
            }
            $return_cases[] = $case;

            return response()->json(
                [
                    'user' =>
                    [
                        'firstname' => $case->client->firstname,
                        'lastname' => $case->client->lastname,
                        'phonenumber' => $case->client->phonenumber
                    ],
                    'cases' => $return_cases
                ]
            );
        } else {
            return response()->json(false, 404);
        }
    }

    public function patient(Request $request, $uuid) {
        $user = User::where(['uuid' => $uuid])->firstOrFail();
        $authUser = Auth::user();
        if($authUser && $user) {

            $cases = Examination::where('patient', '=', $user->uuid)
                                ->with(['client', 'diagnoses', 'closeups', 'overviews', 'locked', 'referrals'])
                                ->get();

            $return_cases = [];
            foreach($cases as $case) {
                $can_edit = $case->locked == null || $case->locked->uuid === $authUser->uuid;
                $case['can_edit'] = $can_edit;
                $case['child_ssn'] = isset($case->child_ssn) ? Crypt::decryptString($case->child_ssn) : null;

                // add icd_codes since it is hidden
                $diagnoses_arr = [];
                foreach($case['diagnoses'] as $diagnose) {
                    $diagnose->makeVisible('icd_codes');
                    $diagnoses_arr[] = $diagnose;
                }

                $return_cases[] = $case;
            }

            return response()->json(
                [
                    'user' =>
                     [
                        'firstname' => $user->firstname,
                        'lastname' => $user->lastname,
                        'phonenumber' => $user->phonenumber
                     ],
                    'cases' => $return_cases
                ]
            );
        } else {
            return response()->json(['error' => 'No user found'], 401);
        }
    }

    public function next(Request $request)
    {
        $physician = $request->user()->uuid;
        $servableRegions = MedUser::where('uuid', '=', $physician)->first()->servable_regions;

        if(!$physician) {
            return response()->json(['error' => 'Not logged in'], 401);
        }

        \DB::listen(function($sql) {
            Log::info('********(next case)*************');
            Log::info($sql->sql);
            Log::info(print_r($sql->bindings, true));
        });

        // Find the first available examination that as not been diagnosed and is not locked by user or null
        $examination = Examination::where('complete', '=', true)
            ->where('diagnosed', '=', false)
            ->where('category', '!=', 'video')
            ->where(function($query) {
                $query->where('payment_type', Examination::PAYMENT_TYPE_PARTNER);
                $query->orWhere(function($orQuery) {
                    $orQuery->where('payment_type', Examination::PAYMENT_TYPE_CARD);
                    $orQuery->whereNotNull('charged');
                    $orQuery->whereNotNull('stripe');
                });
            })
            ->where(
                function ($query) use ($physician) {
                    $query->where('locked_by', '=', $physician)
                        ->orWhereNull('locked_by');
                }
            )
            ->whereHas('client', function($query) use ($servableRegions) {
                $query->whereIn('region', json_decode($servableRegions));
            })
            ->whereNotIn('uuid', function ($query) use ($physician) {
                $query->select('examination')
                    ->where('performed_by', '!=', $physician)
                    ->from('diagnoses');
            })
            ->whereHas('client', function ($query) {
                $query->whereNotIn('partner', ['Oslo'])
                    ->orWhereNull('partner');
            })
            ->with('client.idProof')
            ->with(['diagnoses', 'closeups', 'overviews'])
            ->orderBy('deadline_time', 'asc')
            ->first();

        

        // Lock the examination if one is found
        if ($examination !== null) {
            // $user = USER::where('uuid', '=', $examination->client->uuid)
            //             ->where('region', '=', 'uk')->first();
            // $idproofObj = $user->idProof()->orderBy('created_at','desc')->first();
            // $idproof = $idproofObj->uuid.'.'.$idproofObj->suffix;

            // $examination->client->idProof = $idproof;
            
            $examination->locked_by = $physician;
            $examination->save();

            $client_phone = $examination->client->phonenumber;
            $ssn = null;
            if($examination->client->ssn) {
                $ssn = Crypt::decryptString($examination->client->ssn);
            }
            $examination['child_ssn'] = isset($examination->child_ssn) ? Crypt::decryptString($examination->child_ssn) : null;
            return response()->json(['examination' => $examination, 'client' => $client_phone, 'client_ssn' => $ssn]);
        }
        return response()->json(['examination' => 'notfound'], 404);
    }

    public function lock(Request $request) {
        $this->validate(
            $request, [
                'examination' => 'required',
            ]
        );

        $examination = $request->get('examination');
        $authUser = Auth::user();
        if($authUser) {
            $examination = Examination::where('uuid', $examination)->firstOrFail();
            if($examination) {
                if($examination->locked_by === null || $examination->locked_by === $authUser->uuid) {
                    $examination->locked_by = $authUser->uuid;
                    $examination->save();
                    return response()->json(['status' => 'ok']);
                } else {
                    return response()->json(['status' => 'alreadylocked']);
                }
            } else {
                return response()->json(['examination' => 'notfound'], 404);
            }
        } else {
            return response()->json(['user' => 'notfound'], 404);
        }
    }

    /**
     * Unlock an examination
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $uuid
     * @return \Illuminate\Http\Response
     */
    public function unlock(Request $request, $uuid)
    {
        $physician = $request->user()->uuid;
        $examination = Examination::findOrFail($uuid);

        if (!$physician) {
            return response()->json(['status' => 'notfound']);
        } else if ($examination->locked_by !== null && $examination->locked_by !== $physician) {
            return response()->json(['status' => 'lockedbyother']);
        }

        $examination->locked_by = null;
        $examination->save();

        return response()->json(['status' => 'ok']);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $uuid
     * @return \Illuminate\Http\Response
     */
    public function newimages(Request $request, $uuid)
    {
        $examination = Examination::findOrFail($uuid);
        $reason = $request->input('reason');
        // Send message to user that we need new images for the examination.
        $phonenumber = $examination->client->phonenumber;
        $region = $examination->client->region;
        $this->sendImageRequestToPatient($phonenumber, $region);
        // Reset examination timeline and locking so that the user can update it.
        $examination->locked_by = null;
        $examination->deadline_time = null;
        $examination->complete = false;
        $examination->reject_reason = $reason;
        $examination->rejected_by = $request->user()->uuid;
        $examination->save();

        Log::info('The examination: ' . $examination->uuid . ' requires new images to be processed.');

        return response()->json(['examination' => 'reset']);
    }

    /**
     * Create a new diagnose for an examination
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $uuid
     * @return \Illuminate\Http\Response
     */
    public function diagnosis(Request $request, $uuid)
    {
        try {
            $physician = $request->user()->uuid;

            // handle undefined sent as string due to formData
            $description = $request->input('description') == 'undefined' ? null : $request->input('description');
            $icd_codes = in_array($request->input('icd_codes'), ['undefined', 'null', '', '[]']) ? null : $request->input('icd_codes');
            $request->merge([
                'description' => $description,
                'icd_codes' => $icd_codes
            ]);

            $this->validate(
                $request,
                [
                    'description' => 'required_without:icd_codes',
                    'icd_codes' => 'required_without:description',
                    'is_prescribed' => 'required',
                    'referrals.*' => 'sometimes|mimes:jpg,jpeg,png,pdf|max:10240'
                ]
            );

            $examination = Examination::findOrFail($uuid);

            // Description mandatory apart from video cases
            if ($examination->category != 'video' && $request->input('description') == null) {
                return response()->json(['status' => 'error', 'message' => 'Description required'], 500);
            }

            // Does the auth user have the current lock on the case?
            if ($examination->locked_by !== $physician) {
                Log::info('The examination: ' . $examination->uuid . ' is not locked by the physician trying to update it: ' . $physician);
                // No, then return an error to inform that the user does not have access to update the case.
                return response()->json(['error' => 'You do not have access to this case anymore. Please go back and grab a case.'], 403);
            }
            // Yes, the current user has the right to update the case.
            $phonenumber = $examination->client->phonenumber;
            $region = $examination->client->region;
            $examination->locked_by = null;
            $examination->diagnosed = true;

            DB::beginTransaction();
            $examination->save();

            $diagnosis = Diagnosis::create(
                [
                    'category' => 'UNUSED',
                    'description' => $request->input('description'),
                    'icd_codes' => $request->input('icd_codes'),
                    'performed_by' => $physician,
                    'examination' => $examination->uuid,
                    'is_prescribed' => $request->input('is_prescribed') === 'true',
                ]
            );

            if ($request->hasFile('referrals')) {
                $files = $request->file('referrals');

                foreach ($files as $file) {
                    $referral = Referral::create(
                        [
                            'examination' => $examination->uuid,
                            'diagnosis' => $diagnosis->uuid,
                            'name' => $file->getClientOriginalName(),
                            'type' => $file->getMimeType(),
                            'suffix' => $file->getClientOriginalExtension(),
                            'size_in_kb' => $file->getSize()
                        ]
                    );
                    $imageName = $referral->uuid . '.' . $referral->suffix;
                    $this->S3Upload($imageName, $file);
                }
            }
            DB::commit();

            // case_code will be set only for oslo users, whom we do not want to send notification
            // send sms to patient if doctor is actually setting description (that is reviewing and not setting icd_codes)
            if (!$examination->case_code && $request->input('description') != null) {
                $this->sendUpdateToPatient($phonenumber, $region);
            }

            Log::info('The examination: ' . $examination->uuid . ' has been diagnosed.');

            return response()->json(['status' => 'ok']);
        } catch (Exception $ex) {
            DB::rollback();
            Log::error($ex);
            throw $ex;
        }
    }

    /**
     * Check if the authenticated doctor has deemed this case as interesting.
     *
     * @param Request $request The request sent
     * @param string $uuid The examination uuid that is deemed interesting
     * @return bool Is the examination interesting?
     */
    public function interesting(Request $request, $uuid = null)
    {
        $physician = $request->user()->uuid;

        $interesting = Interesting::where('physician', '=', $physician)->where('examination', '=', $uuid)->first();

        if ($interesting !== null && isset($interesting->has_interest)) {
            return response()->json($interesting->has_interest);
        }

        return response()->json(false);
    }

    /**
     * Update the state for the doctors interest in the case
     *
     * @param Request $request
     * @param string $uuid Examination uuid
     * @return void
     */
    public function interestingUpdate(Request $request, $uuid = null)
    {
        $this->validate(
            $request, [
                'has_interest' => 'required',
            ]
        );
        $physician = $request->user()->uuid;

        if ($uuid !== null && $physician !== null) {
            $interesting = Interesting::firstOrNew(['physician' => $physician, 'examination' => $uuid]);
            $interesting->has_interest = $request->input('has_interest');
            $interesting->save();


            return response()->json($interesting->has_interest);
        }
        return response()->json(['error' => ''], 400);
    }

    public function updatePrivateDescription(Request $request, $uuid = null) {
        $this->validate(
            $request, [
                'icd_codes' => 'required',
            ]
        );

        if($uuid) {
            $diagnosis = Diagnosis::where('uuid', '=', $uuid)->firstOrFail();
            $diagnosis->icd_codes = $request->input('icd_codes');
            $diagnosis->save();
            return response()->json(['status' => 'ok']);
        }

        return response()->json(['error' => ''], 400);
    }

    /**
     * Get all the interesting cases for the current physician
     *
     * @param Request $request
     * @return void
     */
    public function cases(Request $request)
    {
        $physician = $request->user()->uuid;
        $cases = Interesting::where('physician', '=', $physician)
            ->where('has_interest', '=', true)
            ->with(['examination', 'examination.diagnoses', 'examination.closeups', 'examination.overviews'])
            ->get();

        return response()->json(['cases' => $cases]);
    }

    /**
     * Export partner Examination
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $this->validate(
            $request,
            [
                'from' => 'required|date',
                'to' => 'required|date',
                'partner' => 'required'
            ]
        );

        return \Excel::download(new ExaminationsExport($request->from, $request->to, $request->partner), 'export.xlsx');
    }

    /**
     * Generate new jwt token and refresh token
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function refreshToken(Request $request)
    {
        if (!$request->headers->has('refreshToken')) {
            return response()->json(['error' => ''], 400);
        }

        try {
            $credentials = JWT::decode($request->headers->get('refreshToken'), env('JWT_SECRET'), ['HS256']);
        } catch (ExpiredException $e) {
            return response()->json(['error' => 'expired'], 401);
        } catch (Exception $e) {
            return response()->json(['error' => 'token'], 401);
        }

        $user = MedUser::find($credentials->aud);
        // Is the user active?
        if (!$user->active) {
            // No, the user is not active.
            return response()->json(['error' => 'inactive'], 401);
        }

        return response()->json(['token' => $this->jwt($user), 'refreshToken' => $this->jwtRefreshToken($user)]);
    }

    /**
     * Verify Email token and send and save OTP.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function verifyEmailToken(Request $request)
    {
        $this->validate(
            $request,
            [
                'token' => 'required|string',
                'phonenumber' => 'required'
            ]
        );
        $medUser = MedUser::where(['token' => $request->input('token')])->first();
        if (!$medUser) {
            return response()->json(['status' => 'invalid_token']);
        }

        $duplicateMedUser = MedUser::where(['phonenumber' => $request->input('phonenumber')])->exists();
        if ($duplicateMedUser) {
            return response()->json(['status' => 'phone_no_exists']);
        }

        $otp = $this->getToken(4);
        $this->sendOtpToUser($request->input('phonenumber'), $otp, 'en', $medUser->country);

        $medUser->phonenumber = $request->input('phonenumber');
        $medUser->otp = app('hash')->make($otp);
        $medUser->is_email_verified = true;
        $medUser->is_phone_verified = false;
        $medUser->save();

        return response()->json(['status' => 'success']);
    }

    /**
     * Verify phonenumber otp and set password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function setPassword(Request $request)
    {
        $this->validate(
            $request,
            [
                'token' => 'required|string',
                'otp' => 'required',
                'password' => 'required|string|min:6|confirmed',
                'accepted_terms' => 'required|accepted'
            ]
        );

        $medUser = MedUser::where(['token' => $request->input('token')])->first();
        if (!$medUser) {
            return response()->json(['status' => 'invalid_token']);
        }

        if (!Hash::check($request->input('otp'), $medUser->otp)) {
            $medUser->otp_failed_count++;
            $medUser->save();
            return response()->json(['status' => 'invalid_otp']);
        }

        $medUser->otp = null;
        $medUser->token = null;
        $medUser->active = true;
        $medUser->otp_failed_count = 0;
        $medUser->is_phone_verified = true;
        $medUser->password = app('hash')->make($request->input('password'));
        $medUser->save();

        return response()->json(['status' => 'success']);
    }

    /**
     * Invite user (doctor + admin) via email.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function inviteUser(Request $request)
    {
        $this->validate(
            $request,
            [
                'name' => 'required|string',
                'email' => 'required|email',
                'country' => 'required|in:no,se,de,uk',
                'servable_regions' => 'required|array',
                'is_doctor' => 'required|boolean',
                'is_superadmin' => 'required|boolean'
            ]
        );

        $user = MedUser::where(['email' => $request->input('email')])->first();
        if ($user) {
            return response()->json(['status' => 'email_exists']);
        }

        $token = uniqid();

        DB::beginTransaction();
        $medUserData = [
            'display_name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => app('hash')->make(str_random(20)),  // generate a random password (user will set a new one by himself over email link)
            'country' => $request->input('country'),
            'currency' => self::COUNTRY_CURENCY_MAP[$request->input('country')],
            'servable_regions' => json_encode($request->input('servable_regions')),
            'active' => false,
            'superadmin' => (bool) $request->input('is_superadmin'),
            'is_doctor' => (bool) $request->input('is_doctor'),
            'token' => $token
        ];
        MedUser::create($medUserData);

        $variables = [
            "redirect_url" => config('constants.doctor_onboard_url.' . env('APP_ENV')) . "/" . $token,
            "name" => $request->input('name')
        ];

        $response = $this->sendMailViaMailjet("support@snapmed.no", $request->input('email'), (int) env('MJ_USER_ONBOARD_TEMPLATE_ID'), $variables);
        if (!$response->success()) {
            return response()->json(['status' => 'email_send_failed']);
        }

        DB::commit();
        return response()->json(['status' => 'success']);
    }

    /**
     * Get diagnoses without icd codes
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getBlankIcdCodes(Request $request)
    {
        $count = $diagnose = Diagnosis::where('icd_codes', '=', null)
            ->orWhereNull('icd_codes')
            ->count();

        $diagnose = Diagnosis::where('icd_codes', '=', null)
            ->orWhereNull('icd_codes')
            ->with(['examination', 'examination.closeups', 'examination.overviews', 'examination.client'])
            ->first();

        if ($diagnose) {
            $diagnose->makeVisible('icd_codes');
            return response()->json(['diagnose' => $diagnose, 'count' => $count]);
        }

        return response()->json(['diagnose' => 'notfound'], 404);
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
    // Internal heler functions

    /**
     * Private helper function to get a new Mailjet http client
     *
     * @return \GuzzleHttp\Client
     */
    private function getMailJetClient()
    {
        $mj = new \Mailjet\Client(env('MJ_API_KEY'), env('MJ_SECRET_KEY'), true, ['version' => 'v3.1']);
        return $mj;
    }

    /**
     * Private helper function to send email via Mailjet
     *
     * @return \GuzzleHttp\Client
     */
    private function sendMailViaMailjet($from, $to, $templateID, $variables)
    {
        $client = $this->getMailJetClient();
        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => "$from"
                    ],
                    'To' => [
                        [
                            'Email' => "$to"
                        ]
                    ],
                    'TemplateID' => $templateID,
                    'TemplateLanguage' => true,
                    "Variables" => $variables
                ]
            ]
        ];
        $response = $client->post(Resources::$Email, ['body' => $body]);
        return $response;
    }

    /**
     * The function verifies the password and then generates a OTP code that is sent to the user.
     *
     * @param User   $user     The user in question.
     * @param String $password The password to verify.
     *
     * @return boolean based on wether password is correct or not.
     */
    protected function attemptLogin($user, $password)
    {
        // Is the password correct
        if (Hash::check($password, $user->password)) {
            // Generate one-time-password
            $otp = $this->getToken(4);
            $this->sendOtpToUser($user->phonenumber, $otp, 'en', $user->country);
            // Encrypt the password and save it.
            $user->otp = app('hash')->make($otp);
            $user->save();
            return true;
        }
        return false;
    }

    /**
     * Generates a number bewteen min and max.
     *
     * @param int $min minimum
     * @param int $max maximum
     *
     * @return int random number
     */
    protected function cryptoRandSecure($min, $max)
    {
        $range = $max - $min;
        if ($range < 1) {
            return $min; // not so random...
        }
        $log = ceil(log($range, 2));
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd > $range);

        return $min + $rnd;
    }

    /**
     * Generates a token used for one-time-password being sent to the user
     *
     * @param Number $length The length of the token to be generated
     *
     * @return String token generated
     */
    protected function getToken($length)
    {
        $token = "";
        $codeAlphabet = "ABCDEFGHJKLMNPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijkmnopqrstuvwxyz";
        $codeAlphabet.= "123456789";
        $max = strlen($codeAlphabet);

        if (env('APP_ENV') === 'local') {
            return '1234';
        }

        for ($i=0; $i < $length; $i++) {
            $token .= $codeAlphabet[$this->cryptoRandSecure(0, $max-1)];
        }

        return $token;
    }
    /**
     * Create a new token.
     *
     * @param MedUser $user the user to create a new JWT token.
     *
     * @return string jwt token for the authenticated user.
     */
    protected function jwt(MedUser $user)
    {
        $payload = [
            'iss' => "snapmed", // Issuer of the token
            'sub' => $user->uuid, // Subject of the token
            'iat' => time(), // Time when JWT was issued.
            'exp' => time() + 60 * 60 * 2 // Expiration time
        ];

        // As you can see we are passing `JWT_SECRET` as the second parameter that will
        // be used to decode the token in the future.
        return JWT::encode($payload, env('JWT_SECRET'));
    }

    /**
     * Create a new refresh token.
     *
     * @param MedUser $user the user to create a new JWT refresh token.
     *
     * @return String jwt refresh token for the authenticated user.
     */
    protected function jwtRefreshToken(MedUser $user)
    {
        $payload = [
            'iss' => "snapmed",
            'aud' => $user->uuid,
            'iat' => time(),
            'exp' => time() + 60 * 60 * 24 * 7
        ];

        return JWT::encode($payload, env('JWT_SECRET'));
    }

    /**
     * Sends one-time-password to user via twillio.
     *
     * @param String $phonenumber The phonenumber to send to.
     * @param String $otp         The otp to send to the user.
     *
     * @return void
     */
    protected function sendOtpToUser($phonenumber, $otp, $lang = 'nb', $region)
    {
        $lang = $lang === 'en' ? 'en' : 'nb';
        $messages = array(
            'nb' => "Din engangskode er: $otp",
            'en' => "Your one-time passcode is: $otp"
        );
        try {
            SmsSender::sendSms($phonenumber, $messages[$lang], $region);
        } catch (Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }

    protected function sendUpdateToPatient($phonenumber, $region = 'no')
    {
        $messages = array(
            'no' => "Takk for at du bruker Dr. Dropin Hud. Ditt svar er klart. Logg inn her: response.drdropin.snapmed.no",
            'se' => "Takk för att du använder Dr. Dropin Hudcentrum.Vi har behandlat ditt ärende. Logga inn här för att se ditt svar: response.drdropin.snapmed.se",
        );
        try {
            SmsSender::sendSms($phonenumber, $messages[$region], $region);
        } catch (Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }

    protected function sendImageRequestToPatient($phonenumber, $region = 'no')
    {
        $messages = array(
            'no' => "Dr. Dropin Hud oppdatering\n\nVi trenger nye bilder for å gjøre en vurdering. Vennligst logg inn på response.drdropin.snapmed.no og legg inn nye bilder.",
            'se' => "Dr. Dropin Hud uppdatering.\n\nHudläkaren behöver att du skickar in nya bilder för att bedöma ditt fall. Logga in på response.drdropin.snapmed.se för att skicka in nya bilder.",
        );
        try {
            SmsSender::sendSms($phonenumber, $messages[$region], $region);
        } catch (Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
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
            'Key'    => "referrals/".$imageName,
            'SourceFile' => $file			
        ]);
    }
}
