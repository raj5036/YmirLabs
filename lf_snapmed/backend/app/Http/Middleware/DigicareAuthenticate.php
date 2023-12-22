<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;

class DigicareAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $token = $request->headers->get('Authorization');
        if (!$token || empty($token)) {
            return response()->json(['error' => 'Authorization token not provided.'], 401);
        }
        try {
            $credentials = JWT::decode($token, env('JWT_SECRET'), ['HS256']);
        } catch(ExpiredException $e) {
            return response()->json(['error' => 'Provided token is expired.'], 401);
        } catch(SignatureInvalidException $e) {
            return response()->json(['error' => 'Signature verification failed.'], 401);
        } catch(Exception $e) {
            return response()->json(['error' => 'An error while decoding token.'], 401);
        }
        return $next($request);
    }
}
