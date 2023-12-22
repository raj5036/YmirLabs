<?php

namespace App\Http\Controllers;

use App\Examination;
use Illuminate\Http\Request;
use Log;
use \DateTime;

class WebHookController extends Controller
{
    public function __construct()
    { }

    /**
     * Stripe payment intent webhook
     *
     * @param Request $request
     */
    public function stripeWebhook(Request $request)
    {
        // We got a payment intent succeeded action
        if ($request->input('type') === 'payment_intent.succeeded') {
            $data = $request->input('data');
            if ($data['object'] && $data['object']['id'] && $data['object']['object'] && $data['object']['object'] === 'payment_intent') {
                $payment_intent_id = $data['object']['id'];

                $examination = Examination::where('stripe', $payment_intent_id)->first();
                if ($examination) {
                    Log::info('Found examination with payment_intent_id, updating charged status -> ' . $payment_intent_id);
                    $examination->charged = new \DateTime('now');
                    $examination->save();
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
