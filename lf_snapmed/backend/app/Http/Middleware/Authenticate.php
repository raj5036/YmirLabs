<?php

namespace App\Http\Middleware;

use Log;

use App\User;
use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Facades\Hash;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     *
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request The request to handle.
     * @param \Closure                 $next    If all goes fine goto next.
     * @param string|null              $guard   Guard parameter.
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $token = $request->headers->get('token');

        if (!$token || empty($token) || substr_count($token, '.') !== 2) {
            // Unauthorized response if token not there
            return response()->json(['error' => 'Token not provided.'], 401);
        }
        try {
            $credentials = JWT::decode($token, env('JWT_SECRET'), ['HS256']);
        } catch(ExpiredException $e) {
            return response()->json(['error' => 'Provided token is expired.'], 400);
        } catch(Exception $e) {
            return response()->json(['error' => 'An error while decoding token.'], 400);
        }
        $user = User::find($credentials->sub);

        $routeArr = $request->route();
        $routeName = $routeArr[1]['as'];
        // Does the user have a pending token present?
        if (isset($user->otp) && $routeName !== 'userSubmitCheck' && $routeName !== 'userAdditionalData' && $routeName !== 'examinationUpdate' && $routeName !== 'checkExamination' && $routeName !== 'examination' && $routeName !== 'userUpdate' && $routeName !== 'setEmail' && $routeName !== 'userSetPhone' && $routeName !== 'submitImageCase' && $routeName !== 'verifyPassword' && $routeName !== 'userSubmitCheckUK' && $routeName !== 'checkpromo' && $routeName !== 'getpromo' && $routeName !== 'charge' && $routeName !== 'forgot-password') {
            // Yes.
            // Is this a request for verification of the otp?
            if (isset($routeArr[2]['otp'])) {
                // Yes, this is a request to verify the otp.
                $requestOtp = $routeArr[2]['otp'];
                // Yes, is the otp valid with the token in the db?
                if (!Hash::check($requestOtp, $user->otp)) {

                    // Update otp failed count
                    $user->otp_failed_count++;
                    $user->save();

                    // No, the otp does not match. Do not let the user pass in.
                    return response()->json(['error' => 'unauthorized'], 401);
                } else {
                    // if correct, and if user has tried multiple times, reset otp failed count
                    if ($user->otp_failed_count > 0) {
                        $user->otp_failed_count = 0;
                        $user->save();
                    }
                }
            } else {
                // No, this is not a verification request - so unathorize it
                return response()->json(['error' => 'unauthorized'], 402);
            }
        }
        $this->auth = $user;
        $request->auth = $user;

        return $next($request);
    }
}
