<?php

namespace App\Http\Controllers;

use Auth;
use Log;


use App\User;
use App\Examination;
use App\PartnerUserClaimNumber;
use App\Notification;
use App\Notifications\SmsSender;
use \Stripe\Token;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Stripe\Charge;

class AuthController extends Controller
{
    public function __construct()
    { }

    /**
     * Verify user's email
     */
    public function verifyEmail(Request $request)
    {
        $this->validate(
            $request,
            [
                'token' => 'required|string',
                'region' => 'required|in:co,no,se,uk,de'
            ]
        );
        $user = User::where(['token' => $request->input('token')])->first();

        if (!$user) {
            return response()->json(['status' => false, 'code' => 'expire'], 200);
        } else {
            $user->is_email_verified = true;
            $user->token = null;
            $user->save();
            return response()->json(['status' => true, 'code' => 'verified'], 200);
        }
    }

    /**
     * check whether user email is verified or not
     */

    public function checkVerifiedEmail(Request $request)
    {
        $user = Auth::user();
        return response()->json(['status' => true, 'is_email_verified' => $user->is_email_verified], 200);
    }


    /**
     * Inits a bankID login and returns the operationUrl and operationId to the client
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function initBankId(Request $request)
    {
        $this->validate(
            $request,
            [
                'provider' => 'sometimes|in:no-bankid,se-bankid',
                'region' => 'required|in:no,se',
                'requestFrom' => 'required|in:flow,dashboard'
            ]
        );
        $provider = $request->input('provider');

        if (!$provider) {
            $provider = 'no-bankid';
        }
        $returnUrl = config('constants.bank_id_return_url.' . $request->input('requestFrom'). '.' .env('APP_ENV') . '.'  . $request->input('region'));
        $client = $this->getBankIdClient();
        $body = json_encode([
            'Merchant' => env('BANKID_MERCHANT'),
            'ReturnUrl' => $returnUrl,
            'providers' => [$provider]
        ]);
        $response = $client->post('auth', [
            'body' => $body,
        ]);

        if ($response->getStatusCode() == 201 || $response->getStatusCode() == 200) {
            $json_response = json_decode(($response->getBody()->getContents()));
            $return_arr = [
                'operationUrl' => $json_response->operationUrl,
                'operationId' => $json_response->operationId
            ];
            return response()->json($return_arr);
        }

        return response()->json(['error' => 'request.failed'], 500);
    }

    /**
     * Log into system with a bankID operationId
     *
     * @param Request $request
     */
    public function loginBankId(Request $request)
    {
        $this->validate(
            $request,
            [
                'locale' => 'required|in:nb,en,sv,de,gb',
                'region' => 'required|in:co,no,se,uk,de',
                'operationId' => 'required'
            ]
        );

        $operationId = $request->input('operationId');
        $register = $request->input('register') === true;

        if (!$operationId) {
            return response()->json(['error' => 'login.failure'], 400);
        }

        // TODO: sprinkle with Sentry logging
        $client = $this->getBankIdClient();
        $response = $client->get('operation/' . $operationId);
        if ($response->getStatusCode() == 200) {
            $json_response = json_decode(($response->getBody()->getContents()));
            if ($json_response->state === 'STATE_COMPLETED') {
                $ssn = $json_response->ssn;
                // if we're on local dev environment and ssn is empty it means we're
                // probably using a test user, so grab the SSN from the .env file since
                // test users do not return SSN
                if (env('APP_ENV') === 'local' || env('APP_ENV') === 'test' && !$ssn) {
                    $ssn = env('BANKID_TEST_SSN');
                }
                if ($ssn) {
                    $hashedSsn = sha1($ssn);
                    $encryptedSsn = Crypt::encryptString($ssn);
                    
                    // if it is a request for a  Aurora
                    if ($request->input('email')) {
                        $user = User::where('email', $request->input('email'))->first();
                        if ($user) {
                            $user->ssn = $encryptedSsn;
                            $user->ssn_hash = $hashedSsn;
                            $user->lastname = $json_response->name;
                            $user->save();
                        }
                        return response()->json(
                            [
                                'name' => $json_response->name,
                                'uuid' => $user->uuid,
                                'state' => $json_response->state,
                                'token' => $this->jwt($user)
                            ]
                        );
                    }
                    $user = User::where('ssn_hash', $hashedSsn)->orderBy('updated_at', 'desc')->first();
                    if ($user && $user->login_method === User::LOGIN_METHOD_BANKID) {
                        $name = $user->firstname ? $user->firstname . ' ' . $user->lastname : $user->lastname;
                        return response()->json(
                            [
                                'name' => $name,
                                'uuid' => $user->uuid,
                                'state' => $json_response->state,
                                'token' => $this->jwt($user)
                            ]
                        );
                    } else {
                        // If 'register' is set to true
                        if ($register && !$user) {
                            $user = User::create(
                                [
                                    'ssn' => $encryptedSsn,
                                    'ssn_hash' => $hashedSsn,
                                    'lastname' => $json_response->name,
                                    'locale' => $request->input('locale'),
                                    'region' => $request->input('region'),
                                    'password' => app('hash')->make(str_random(20)),    // generate a random password for this user (never used since they will use bankID to login)
                                    'login_method' => User::LOGIN_METHOD_BANKID
                                ]
                            );

                            return response()->json(
                                [
                                    'name' => $json_response->name,
                                    'uuid' => $user->uuid,
                                    'state' => $json_response->state,
                                    'token' => $this->jwt($user)
                                ]
                            );
                        }
                        // user not found, OR login method not bankID
                        // return ssn_hash as password here?
                    }
                }
            } else {
                app('sentry')->captureMessage('BankID api failed with state: ' . $json_response->state);
                // state something else than COMPLETED, return state
                return response()->json([
                    'state' => $json_response->state
                ]);
            }
        }
        return response()->json(['error' => 'login.failure'], 400);
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function loginStorebrand(Request $request)
    {

        $guid = $request->input('guid');
        $brand = $request->input('brand');
        $this->validate(
            $request,
            [
                'locale' => 'required|in:nb,en,sv,de,gb',
                'region' => 'required|in:co,no,se,uk,de',
                'guid' => 'required',
                'brand' => 'sometimes|string|max:3'
            ]
        );

        if (!$guid) {
            return response()->json(['error' => 'login.failure'], 400);
        }

        $client = new \GuzzleHttp\Client();

        // old api
        if (!$brand) {
            $response = $client->request('GET', env('STB_API_ENDPOINT') . $guid, [
                'auth' => [env('STB_CLIENT_ID'), env('STB_CLIENT_SECRET')]
            ]);
            // new api
        } else {
            // note - other `brands` will come here later (stb/dkv/spp) - stb = norway
            $response = $client->request('GET', env('STB_API_ENDPOINT_NEW') . $guid, [
                'headers' => [
                    'Api-Version' => 'v1'
                ],
                'auth' => [env('STB_CLIENT_ID_NEW'), env('STB_CLIENT_SECRET_NEW')]
            ]);
        }

        if ($response->getStatusCode() == 200) {
            $json_response = json_decode(($response->getBody()->getContents()));
            if ($json_response->ssn) {
                $ssn = $json_response->ssn;

                if (!$ssn) {
                    return response()->json(['error' => 'login.missing-info'], 400);
                }

                $firstname = $json_response->firstName;
                $lastname = $json_response->lastName;
                $phonenumber = @$json_response->phoneNumber ?: @$json_response->phonenumber;

                $policynumber = '';
                $claimnumber = '';
                $childSsn = null;
                if (property_exists($json_response, 'policynumber') && $json_response->policynumber) {
                    $policynumber = $json_response->policynumber;
                }

                // note as per 10.03.2021 the API would return the string "null" for empty claim number, this this check
                if (property_exists($json_response, 'claimNumber') && $json_response->claimNumber && $json_response->claimNumber !== 'null' && $json_response->claimNumber != '') {
                    $claimnumber = $json_response->claimNumber;
                }

                if (property_exists($json_response, 'childSsn') && $json_response->childSsn) {
                    $childSsn = $json_response->childSsn;
                }

                $encryptedSsn = Crypt::encryptString($ssn);
                $hashedSsn = sha1($ssn);
                Log::info('Logging in STB user: ' . $phonenumber . ' (policy:' . $policynumber . ', claimnumber: ' . $claimnumber . ')');

                $user = User::where('ssn_hash', $hashedSsn)->orderBy('updated_at', 'desc')->first();
                if (!$user) {
                    // create user since it does not exist
                    $user = User::create(
                        [
                            'ssn' => $encryptedSsn,
                            'ssn_hash' => $hashedSsn,
                            'firstname' => $firstname,
                            'lastname' => $lastname,
                            'phonenumber_not_verified' => $phonenumber,
                            'locale' => $request->input('locale'),
                            'region' => $request->input('region'),
                            'password' => app('hash')->make(str_random(20)),    // generate a random password for this user (never used since they will use bankID to login)
                            'partner_policynumber' => $policynumber,
                            'partner' => config('constants.partner_stb'),
                            'brand' => $brand,
                            'login_method' => User::LOGIN_METHOD_BANKID
                        ]
                    );
                } else {
                    Log::info('Logging in STB user: user already existed with uuid ' . $user->uuid);

                    // just save the information, in case some of it has changed since last
                    // NOTE: we do not update phonenumber here since that would require us to set phonenumber = null and phonenumber_not_verified
                    // to the new phonenumber. This means users could end up w/o a phonenumber if they 1) login and go through entire flow and submit a case and
                    // 2) later login and stop before verifying phone (since phonenumber would be set to null in this step)
                    $user->ssn = $encryptedSsn;
                    $user->ssn_hash = $hashedSsn;
                    $user->firstname = $firstname;
                    $user->lastname = $lastname;
                    $user->locale = $request->input('locale');
                    $user->region = $request->input('region');
                    $user->partner_policynumber = $policynumber;
                    $user->partner = config('constants.partner_stb');
                    $user->brand = $brand;
                    $user->login_method = User::LOGIN_METHOD_BANKID;
                    $user->otp = null;
                    $user->otp_failed_count = 0; // reset failed count here since this is a successful login
                    $user->save();
                }

                if ($claimnumber) {
                    $existingClaim = PartnerUserClaimNumber::where(['user_id' => $user->uuid, 'claimnumber' => $claimnumber])->first();
                    if (!$existingClaim) {
                        $existingClaim = new PartnerUserClaimNumber();
                        $existingClaim->user_id = $user->uuid;
                        $existingClaim->claimnumber = $claimnumber;
                        $existingClaim->partner = config('constants.partner_stb');
                        $existingClaim->save();
                    }
                }


                return response()->json(['token' => $this->jwt($user), 'phonenumber' => $phonenumber, 'policynumber' => $policynumber, 'claim_uuid' => ($claimnumber ? $existingClaim->uuid : ''), 'uuid' => $user->uuid, 'name' => $user->firstname . ' ' . $user->lastname, 'child_ssn' => $childSsn]);
            }
        }

        // Delay response for 500ms to 1000ms to deter brute force attempts
        usleep((rand(500, 1000)) * 1000);
        // No, then return login error.
        return response()->json(['error' => 'login.failure'], 400);
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function loginAurora(Request $request)
    {
        $this->validate(
            $request,
            [
                'locale' => 'required|in:nb,en,sv,de,gb',
                'region' => 'required|in:co,no,se,uk,de',
                'email' => 'required'
            ]
        );
        $email = $request->input('email');
        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = User::create(
                [
                    'locale' => $request->input('locale'),
                    'region' => $request->input('region'),
                    'email' => $email,
                    'password' => app('hash')->make(str_random(20)),    // generate a random password for this user
                    'partner' => 'Aurora',
                    'login_method' => User::LOGIN_METHOD_BANKID
                ]
            );
        } else {
            Log::info('Logging in Aurora user: user already existed with uuid ' . $user->uuid);

            $user->locale = $request->input('locale');
            $user->region = $request->input('region');
            $user->partner = 'Aurora';
            $user->login_method = User::LOGIN_METHOD_BANKID;
            $user->save();
        }
        if ($user->phonenumber) {
            $phonenumber = $user->phonenumber;
        } else {
            $phonenumber = null;
        }
        return response()->json(['token' => $this->jwt($user), 'email' => $user->email, 'phonenumber' => $phonenumber]);
    }

    /**
     * Log in the user to our system. Used only for users that already exist in our system.
     *
     * @param Request $request http request with phonenumber and password.
     *
     * @return http response with jwt token.
     */
    public function login(Request $request)
    {

        if ($request->input('email') !== '') {
            // Delay response for 500ms to 1000ms to deter brute force attempts
            usleep((rand(500, 1000)) * 1000);
            // No, then return login error.
            return response()->json(['error' => 'login.failure'], 400);
        }

        $this->validate(
            $request,
            [
                'phonenumber' => 'required',
                'password' => 'required',
                'region' => 'required|in:co,no,se,uk,de',
            ]
        );

        
        $user = User::where(['phonenumber' => $request->input('phonenumber'), 'region' => $request->input('region'), 'login_method' => User::LOGIN_METHOD_PHONE])->first();
        

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
        return response()->json(['token' => $this->jwt($user)]);
    }

    /**
     * Log in or create user with Phone user for UK flow.
     *
     * @param Request $request http request with phonenumber.
     *
     * @return http response with status of otp verification.
     */
    public function loginPhone(Request $request)
    {
        $this->validate(
            $request,
            [
                'phonenumber' => 'required',
                'locale' => 'required|in:nb,en,sv,de,gb',
                'region' => 'required|in:co,no,se,uk,de',
            ]
        );

        $user = User::where(['region' => $request->input('region'), 'login_method' => User::LOGIN_METHOD_PHONE])
            ->where(function ($query) use ($request) {
            $query->where('phonenumber', '=', $request->input('phonenumber'))
                    ->orWhere('phonenumber_not_verified', '=', $request->input('phonenumber'));
            })->first();

        $newUser = False;
        // Is there a user already registered in our system?
        //No, then create new user.
        if (!isset($user)) {
            $password = " "; //PAssword field can't be null in the DB.
            $user = User::create(
                [
                    'phonenumber_not_verified' => $request->input('phonenumber'),
                    'password' => $password,
                    'login_method' => User::LOGIN_METHOD_PHONE,
                    'locale' => $request->input('locale'),
                    'region' => $request->input('region')
                ]
            );
            $newUser = True;
        }

        // User with incomplete case
        if($user->email === null || $user->password === " " ) {
            $newUser = True;
        }

        if (!Examination::where('patient', '=', $user->uuid)
            ->whereNotNull('charged')
            ->whereNotNull('stripe')
            ->exists()) {
                $newUser = True;
            }

        $otp = $this->getToken(4);
        $this->sendOtpToUser($request->input('phonenumber'), $otp, $user->locale, $user->region);

        // Encrypt the otp and save it.
        $user->otp = app('hash')->make($otp);
        $user->save();

        // The user was logged into our system, return JWT token.
        return response()->json([
            'uuid' => $user->uuid,
            'phonenumber' => $request->input('phonenumber'),
            'newUser' => $newUser
        ]);
    }

    /**
     * Log in if user exist or create new user.
     * Used for video login flow using phone authentication.
     *
     * @param Request $request http request with phonenumber and password.
     * @return http response with jwt token.
     */
    public function loginOrCreateUser(Request $request)
    {
        if ($request->input('email') !== '') {
            // Delay response for 500ms to 1000ms to deter brute force attempts
            usleep((rand(500, 1000)) * 1000);
            // No, then return login error.
            return response()->json(['error' => 'login.failure'], 400);
        }

        $this->validate(
            $request,
            [
                'phonenumber' => 'required',
                'password' => 'required',
                'locale' => 'required|in:nb,en,sv,de,gb',
                'region' => 'required|in:co,no,se,uk,de',
            ]
        );

        $user = User::where(['phonenumber' => $request->input('phonenumber'), 'login_method' => User::LOGIN_METHOD_PHONE])->first();

        // Is there a user already registered in our system?
        //No, then create new user.
        if (!isset($user)) {
            $user = User::create(
                [
                    'phonenumber_not_verified' => $request->input('phonenumber'),
                    'password' => app('hash')->make($request->input('password')),
                    'login_method' => User::LOGIN_METHOD_PHONE,
                    'locale' => $request->input('locale'),
                    'region' => $request->input('region')
                ]
            );
        }
        // Yes, then attempt to login the user.
        if (!$this->attemptLogin($user, $request->input('password'), $request->input('phonenumber'))) {
            usleep(100 * 1000);
            return response()->json(['error' => 'login.failure'], 400);
        }
        // The user was logged into our system, return JWT token.
        return response()->json(['token' => $this->jwt($user), 'uuid' => $user->uuid]);
    }

    /**
     * This is to send email to user in case of forgot password
     */
    public function forgotPassword(Request $request)
    {
        // Since we actually get into this method the token has been verified.
        $user = Auth::user();

        if ($user->email) {
            // if yes then send email with password reset url
            $token = uniqid();
            $variables = [
                "redirect_url" => config('constants.patient_password_reset_url.' . env('APP_ENV') . '.' . $request->input('region')) . "/" . $token
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
        $this->notifyAdmin($user->uuid);
        return response()->json(['status' => 'email_absent']);
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
        $user = Auth::user();

        // do not make changes to User if user does not have an OTP. If no OTP on the User object
        // this method can be called directly (without verifying OTP).
        if ($user->otp) {
            // Lets update the user by removing the otp code, since they are now verified.
            $user->otp = null;
            $user->otp_failed_count = 0; // reset OTP failed count
            // if user doesn't have a phonenumber, but does have a phonenumber_not_verified
            // we can assume the unverified phone number is now verified, since the user
            // successfully verifieid the OTP sent
            if (!$user->phonenumber && $user->phonenumber_not_verified) {
                $user->phonenumber = $user->phonenumber_not_verified;
                $user->phonenumber_not_verified = null;
            }

            $user->save();
            // Does this request include a passwordReset for the user?
            if ($request->input('passwordReset')) {
                // Check if users mail exists.
                if ($user->email) {
                    // if yes then send email with password reset url
                    $token = uniqid();
                    $variables = [
                        "redirect_url" => config('constants.patient_password_reset_url.' . env('APP_ENV') . '.' . $request->input('region')) . "/" . $token
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
                $this->notifyAdmin($user->uuid);
                return response()->json(['status' => 'email_absent']);
            }
        }
        if ($user->email) {
            $email = $user->email;
        } else {
            $email = '';
        }

        return response()->json(['status' => 'ok', 'email' => $email]);
    }

    /**
     * Verify OTP without token.
     *
     * @param String $otp one-time-password
     *
     * @return token and email in the response
     */
    public function verifyOTP(Request $request)
    {
        $this->validate(
            $request,
            [
                'otp' => 'required|string',
                'uuid' => 'required|string',
                'phonenumber' => 'required',
            ]
        );

        $user = User::where(['uuid' => $request->input('uuid'), 'login_method' => User::LOGIN_METHOD_PHONE])->first();

        // Is there is no such user with uuid provided
        if (!isset($user)) {
            return response()->json(['error' => 'login.failure'], 400);
        }

        //If OTP is correct
        if (Hash::check($request->input('otp'), $user->otp)) {

            //If correct email id is present, return the masked email
            $email = ($user->email && trim($user->email) != '') ? $this->obfuscate_email($user->email) : $user->email;

            if ($user->phonenumber_not_verified == $request->input('phonenumber')) {
                $user->phonenumber = $user->phonenumber_not_verified;
                $user->phonenumber_not_verified = null;
                $user->otp_failed_count = 0;
                $user->save();
                return response()->json(['token' => $this->jwt($user), 'email' => $email]);
            } else if ($user->phonenumber == $request->input('phonenumber')) {
                $user->otp_failed_count = 0;
                $user->save();
                return response()->json(['token' => $this->jwt($user), 'email' => $email]);
            } else {
                return response()->json(['error' => 'login.failure', 'message' => 'No such user'], 400);
            }
        } else {
            $current_datetime = date("Y-m-d H:i:s");
            //If incorrect OTP attempt is more than 3 times send out a failure message
            if ($user->otp_failed_count > 3) {
                if (!$user->otp_blocked_till) {
                    //block user for an hour
                    $user->otp_blocked_till = date("Y-m-d H:i:s", strtotime("+1 hours"));
                    $user->save();
                    return response()->json(['error' => 'login.failure', 'message' => 'Too many incorrect OTP attempts, blocked for an hour'], 400);
                } else if ($current_datetime < $user->otp_blocked_till) {
                    return response()->json(['error' => 'login.failure', 'message' => 'Too many incorrect OTP attempts, try again in sometime'], 400);
                } else {
                    // if current time is more than blocked hour,
                    $user->otp_blocked_till = null;
                    $user->otp_failed_count = 1;
                    $user->save();
                    return response()->json(['error' => 'login.failure', 'message' => 'Invalid OTP'], 400);
                }
            } else {
                $user->otp_failed_count = $user->otp_failed_count + 1;
                $user->save();
                return response()->json(['error' => 'login.failure', 'message' => 'Invalid OTP'], 400);
            }
        }
    }

    /**
     * Verify Password with token in the request
     * if password is not set, set the password field for the user
     *
     * @param String $otp one-time-password
     *
     * @return http response
     */
    public function verifyPassword(Request $request)
    {
        $this->validate(
            $request,
            [
                'password' => 'required|string|min:6'
            ]
        );

        // Since we actually get into this method the token has been verified.
        $user = Auth::user();

        // if email is not null and empty
        if ($user->email && trim($user->email) != '') {
            //if password is set for a user
            if ($user->password && trim($user->password) != '') {
                //check if the entered password is correct
                if (Hash::check($request->password, $user->password)) {
                    return response()->json(['status' => 'ok', 'data' => []]);
                } else {
                    return response()->json(['error' => 'login.failure', 'message' => 'Incorrect password'], 400);
                }
            } else {
                $user->password = app('hash')->make($request->input('password'));
                $user->save();
                return response()->json(['status' => 'ok', 'message' => 'New password is set for the user.']);
            }
        }

        return response()->json(['error' => 'login.failure', 'message' => 'Invalid email'], 400);
    }

    /**
     * Log in the partner users to our system.
     *
     * @param Request $request http request with phonenumber and password.
     * @return http response with jwt token.
     */
    public function partnerLogin(Request $request)
    {
        $this->validate(
            $request,
            [
                'phonenumber' => 'required',
                'password' => 'required',
            ]
        );

        $user = User::where(['phonenumber' => $request->input('phonenumber'), 'login_method' => User::LOGIN_METHOD_PHONE, 'partner' => 'Oslo'])->first();

        // Is there a user already registered in our system?
        if (!isset($user) || $user === null) {
            usleep(250 * 1000);
            return response()->json(['error' => 'login.failure'], 400);
        }
        // Yes, then attempt to login the user.
        if (!Hash::check($request->input('password'), $user->password)) {
            usleep(100 * 1000);
            return response()->json(['error' => 'login.failure'], 400);
        }

        // The user was logged into our system, return JWT token.
        return response()->json(['token' => $this->jwt($user)]);
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
    // Internal helper functions

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

    /**
     * Private helper function to mask an email address
     *
     * @return masked email
     */
    private function obfuscate_email($email)
    {
        $em   = explode("@", $email);
        $name = implode('@', array_slice($em, 0, count($em) - 1));
        $len  = floor(strlen($name) / 2);
        return substr($name, 0, $len) . str_repeat('*', $len) . "@" . end($em);
    }

    private function notifyAdmin($uuid)
    {
        try {
            $notifications = Notification::where('type', 'password-reset')->get();
            $prefix = env('APP_ENV') === 'production' ? 'Snapmed:' : 'TEST Snapmed:';
            $message = "$prefix User with UUID $uuid tried to reset the password but email does not exist.";
            foreach ($notifications as $key => $notification) {
                SmsSender::sendSms($notification->physician->phonenumber, $message, $notification->physician->country);
            }
        } catch (Exception $ex) {
            Log::error($ex);
        }
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
}
