<?php

namespace App\Exceptions;

use Log;
use Exception;

use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $exception)
    {
        if (env('APP_ENV') != 'local' && app()->bound('sentry') && $this->shouldReport($exception)) {
            app('sentry')->captureException($exception);
        }

        if ($exception instanceof \Stripe\Error\Base) {
            $body = $exception->getJsonBody();
            $stripeError  = $body['error'];
            $stripeType = isset($stripeError['type']) ? $stripeError['type'] : 'unknown_type';
            $stripeCode = isset($stripeError['code']) ? $stripeError['code'] : 'unknown_code';
            $stripeDeclineCode = isset($stripeError['decline_code']) ? $stripeError['decline_code'] : 'unknown_decline_code';
            Log::error("Stripe error: type[${stripeType}] code: [${stripeCode}] decline_code[${stripeDeclineCode}]");
        }

        Log::error('There was an error handling the request: ' . $exception->getMessage());
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof ModelNotFoundException) {
            return response()->json(['error' => 'Model Not Found'], 404);
        } else if ($exception instanceof \Stripe\Error\Base) {
            $body = $exception->getJsonBody();
            $stripeError  = $body['error'];
            return response()->json([
                'error' => 'stripe.error',
                'error.message' => $exception->getMessage(),
                'stripe.type' => isset($stripeError['type']) ? $stripeError['type'] : 'unknown',
                'stripe.code' => isset($stripeError['code']) ? $stripeError['code'] : 'unknown',
                'stripe.decline_code' => isset($stripeError['decline_code']) ? $stripeError['decline_code'] : 'unknown',
            ], 502);
        } else {
            $errorMessage = $exception->getMessage();
            if (substr_compare($errorMessage, 'file_get_contents', 0, strlen('file_get_contents')) === 0) {
                $errorMessage = 'Integration does not work correctly';
            }
            return response()->json([
                'error' => 'unknown.error',
                'error.message' => $errorMessage,
            ], 500);
        }
    }
}
