<?php

namespace App\Http\Middleware;

use Log;

use App\MedUser;
use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Facades\Hash;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

class MedAuthenticate
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
            return response()->json(['error' => 'token'], 401);
        }
        try {
            $credentials = JWT::decode($token, env('JWT_SECRET'), ['HS256']);
        } catch(ExpiredException $e) {
            return response()->json(['error' => 'expired'], 401);
        } catch(Exception $e) {
            return response()->json(['error' => 'token'], 401);
        }
        $medUser = MedUser::find($credentials->sub);
        // Is the user active?
        if (!$medUser->active) {
            // No, the user is not active.
            return response()->json(['error' => 'inactive'], 401);
        }
        // Yes, the user is active so continue the check.

        // Does the user have a pending token present?
        if (isset($medUser->otp)) {
            // Yes.
            // Is this a request for verification of the otp?
            if (isset($request->route()[2]['otp'])) {
                // Yes, this is a request to verify the otp.
                $requestOtp = $request->route()[2]['otp'];
                // Yes, is the otp valid with the token in the db?
                if (!Hash::check($requestOtp, $medUser->otp)) {
                    // No, the otp does not match. Do not let the user pass in.
                    return response()->json(['error' => 'unauthorized'], 401);
                }
            } else {
                // No, this is not a verification request - so unathorize it
                return response()->json(['error' => 'unauthorized'], 401);
            }
        }

        // Check if route is for superadmin.
        if ($guard == 'superadmin') {
            // Is user superadmin?
            if (!$medUser->superadmin) {
                return response()->json(['error' => 'unauthorized'], 401);
            }
        }
        $this->auth = $medUser;
        $request->auth = $medUser;

        return $next($request);
    }
}
