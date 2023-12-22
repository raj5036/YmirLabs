<?php

namespace App\Http\Controllers;

use Auth;
use Log;


use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    { }

    /**
     * Email Subscription
     *
     * @param Request $request
     */
    public function emailSubscription(Request $request)
    {
        $this->validate(
            $request,
            [
                'uuid' => 'required',
                'subscription_email' => 'required'
            ]
        );
        $user = User::findOrFail($request->input('uuid'));
        $user->subscription_email = $request->input('subscription_email');
        $user->is_subscribed = true;
        $user->save();
        return response()->json(['status' => 'User Subscribed'], 200);
    }

    /**
     * Set User Email
     *
     * @param Request $request
     */
    public function setEmail(Request $request)
    {
        $this->validate(
            $request,
            [
                'email' => 'required',
                'region' => 'required|in:co,no,se,uk,de'
            ]
        );

        $alreadyUserExists = User::where(['email' => $request->input('email')])->first();

        if ($alreadyUserExists) {
            return response()->json(['status' => false, 'code' => 'Email Already Exists'], 200);
        }

        $user = Auth::user();
        if ($user) {
            $token = uniqid();
            $user->email = $request->input('email');
            $user->is_email_verified = false;
            $user->token = $token;

            $locale = @$user->locale ?: 'en';

            $variables = [
                "redirect_url" => config('constants.patient_verification_url.' . env('APP_ENV') . '.' . $request->input('region')) . "/" . $token,
                "line_1" => config('constants.email_verify_msg.' . $locale . '.line_1'),
                "line_2" => config('constants.email_verify_msg.' . $locale . '.line_2'),
            ];

            $response = $this->sendMailViaMailjet("support@snapmed.no", $request->input('email'), (int) env('MJ_PATIENT_EMAIL_VERIFY'), $variables);
            if (!$response->success()) {
                return response()->json(['error' => 'email_send_failed'], 500);
            }
            $user->save();
            return response()->json(['status' => true, 'code' => 'updated'], 200);
        } else {
            return response()->json(['error' => 'User Not Found'], 400);
        }
    }

    /**
     * Set *unverified* phone number of exisrting user
     *
     * Note, this request does not require verification of existing token (if any) it will send out
     * a new token that will need to be verified (or this function can be called again to send out
     * token). This request will only work for users logged in through a partner network (verified)
     *
     * @param Request $request
     */
    public function userSetPhone(Request $request)
    {
        $this->validate(
            $request,
            [
                'phonenumber' => 'required',
            ]
        );

        $phonenumber = $request->input('phonenumber');

        // Get the user that is making the request
        $authUser = Auth::user();
        $user = User::findOrFail($authUser->uuid);

        if ($user) {
            // once you reach 4 failed tries this request will take longer and longer
            // 10 tries = 9.5 to 11 seconds.
            // The otp failed count will be reset on successful login (or OTP verification)
            if ($user->otp_failed_count > 3) {
                usleep((rand(500, 1000)) * (intval($user->otp_failed_count) * 1000));
            }

            try {
                $otp = $this->getToken(4);
                $this->sendOtpToUser($phonenumber, $otp, $user->locale, $user->region);
            } catch (\Exception $e) {
                // probably something wrong with the number
                return response()->json(['status' => 'phone.failure']); // note we don't want to send an 400 error here since it will log out the user on the client side
            }
            // Encrypt the password and save it.
            $user->otp  = app('hash')->make($otp);
            $user->phonenumber_not_verified = $phonenumber;
            $user->save();
            return response()->json(['status' => 'ok']);
        }

        return response()->json(['error' => 'login.failure'], 400);
    }

    /**
     * Verify JWT token from Auth Service and update password for logged out user.
     *
     * @param String $loginType
     * @param String $token jwt token
     *
     * @return http response with status of how this went.
     */
    public function updatePassword(Request $request)
    {
        $this->validate(
            $request,
            [
                'token' => 'required|string',
                'password' => 'required|string|min:6|confirmed',
                'region' => 'required|in:co,no,se,uk,de'
            ]
        );

        $user = User::where(['token' => $request->input('token')])->first();

        if (!$user) {
            return response()->json(['status' => false, 'code' => 'expire'], 200);
        }

        $user->is_email_verified = true;
        $user->token = null;
        $user->password = app('hash')->make($request->input('password'));
        $user->save();
        Log::info('Password updated for user: ' . $user->uuid);
        return response()->json(['status' => true, 'code' => 'updated', 'login_redirect_url' => config('constants.login_redirect_url.' . env('APP_ENV') . '.' . $request->input('region'))], 200);
    }

    /**
     * Change password of logged in user.
     *
     * @param Request $request post request.
     * @return http response
     */
    public function changePassword(Request $request)
    {
        $this->validate(
            $request,
            [
                'current_password' => 'required',
                'new_password' => 'required|string|min:6|confirmed'
            ]
        );

        if (!(Hash::check($request->get('current_password'), Auth::user()->password))) {
            return response()->json([
                'status' => false,
                'code' => 'match_err'
            ], 200);
        }

        if (strcmp($request->get('current_password'), $request->get('new_password')) == 0) {
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
}
