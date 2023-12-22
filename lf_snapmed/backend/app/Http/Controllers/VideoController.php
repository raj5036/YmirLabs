<?php

namespace App\Http\Controllers;


use Auth;
use Log;

use \DateTime;

use App\User;
use App\Examination;
use App\PartnerUserClaimNumber;
use \Stripe\Customer;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VideoController extends Controller
{
    public function __construct()
    { }

    /**
     * Return open slots for the week provided as parameter, if no parameter give slots for current week.
     *
     * @param int $year the year to lookup open slots for.
     * @param int $week the week to lookup open slots for.
     * @return http response
     */
    public function video_slots(Request $request, $year = false, $week = false)
    {
        $this->validate(
            $request,
            [
                'region' => 'required|in:co,no,se,uk,de'
            ]
        );
        $region = $request->input('region');
        $result = [];
        if ($year && $week) {
            $result['year'] = $year;
            $result['week'] = $week;
        } else {
            $tmp = $this->getWeekYear();
            $result['year'] = $tmp['year'];
            $result['week'] = $tmp['week'];
        }
        $dates = $this->getStartAndEndDate($result['week'], $result['year']);
        $result['from_date'] = $dates['from_date'];
        $result['to_date'] = $dates['to_date'];

        $slots = $this->getAvailableSlots($dates, $region);
        $result['slots'] = $slots;

        return response()->json($result, 200);
    }

    /**
     * Book an appointment and charge the client for the hour booked.
     *
     * @param Request $request post request containing all data to book an appointment and charging for it.
     * @return http response
     */
    public function video_charge(Request $request)
    {
        $makePlans = $this->shouldMakePlans();
        $this->validate(
            $request,
            [
                // Appointment information
                'appointment.id' => 'required',
                'appointment.free' => 'required|accepted',
                'appointment.timestamp' => 'required|date',
                'appointment.timestamp_end' => 'required|date',
                'appointment.available_resources' => 'required|array',
                // Client information
                'client.description' => 'required',
                'client.payment_method_id' => 'required',
                'client.promoCode' => 'nullable|string',
                'client.amount' => 'required',
                'client.currency' => 'required',
                'client.uuid' => 'required',
                'region' => 'required|in:co,no,se,uk,de'
            ]
        );

        if ($request->input('client.uuid')) {
            $user = User::where('uuid', $request->input('client.uuid'))->first();
            Log::info('Found user ' . $user);
        }

        // Build booking request for sending to MakePlans.
        $resources = $request->input('appointment.available_resources');
        $appointment_timestamp = $request->input('appointment.timestamp');
        $region = $request->input('region');
        $service_id = config('constants.make_plans_map.' . $region . '.service_id');

        $appointment = array(
            'service_id' => $service_id,
            'resource_id' => $resources[array_rand($resources)],
            'booked_from' => $appointment_timestamp,
            'booked_to' => $request->input('appointment.timestamp_end'),
            'notes' => json_encode($request->input('client.description')),
            'person_attributes' => array(
                'name' => $user->lastname,
                'email' => $user->email,
                'phonenumber' => $user->phonenumber
            )
        );


        $promoCode = $request->input('client.promoCode');
        $clientAmount = $request->input('client.amount');
        $currency = $request->input('client.currency');
        $amount = config('constants.make_plans_hour_price_map.' . $currency);

        if ($request->input('client.promoCode')) {
            $promoCode = strtoupper($promoCode);
            Log::info('Promo code used: ' . $promoCode);
            $promoData = $this->recalculateAmountWithPromo($amount, $promoCode, null, null, $currency, 'vs');
            $amount = $promoData['amount'];

            // This will help us add log later
            if ($promoData['promoApplied']) {
                $promoApplied = true;
            } else {
                $promoApplied = false;
            }
        } else {
            $promoApplied = false;
        }

        if ($amount != $clientAmount) {
            $errorMessage = 'Client amount [' . $clientAmount . '] does not match servers: ' . $amount;
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

        
        // Request booking

        Log::info('- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - ');
        if ($makePlans) {
            $booking = $this->bookAppointment($appointment, $region);
        } else {
            $booking['booking'] = [
                'id' => 'TEST',
                'booked_from' => 'test'
            ];
        }
        Log::info('Booking created: ' . $booking['booking']['id'] . ' ' . $booking['booking']['booked_from']);
        // Charge for the booking
        $charged = $this->chargeAppointment($request, $amount);

        if (isset($charged['success']) && $charged['success'] && $makePlans) {
            $this->updateBookingAfterPayment($booking['booking']['id'], $charged['id'], $charged['description'], $region);
        }

        // if we got a client.uuid this is a bankID user or partner user w/o coverage, where we want
        // to create an Examination for this video call

        if ($user) {
            $dt = new \DateTime($appointment_timestamp);
            $deadline = 24; // always 24 hr deadline for partner users
            $deadline_time = $dt->modify('+' . $deadline . ' hour');

            $examinationObj = [
                'patient' => $user->uuid,
                'deadline' => $deadline,
                'deadline_time' => $deadline_time,
                'category' => 'video',
                'other_description' => json_encode($request->input('client.description')),
                'stripe' => $charged['id'],
                'payment_type' => Examination::PAYMENT_TYPE_CARD
            ];
            Log::info('Creating Examination with stripe=' . $charged['id']);
            if (isset($charged['success']) && $charged['success']) {
                $examinationObj['charged'] = new \DateTime('now');  // just set to charged right away if payment succeeded w/o 3DSecure
            }

            // Create an examination so admin can send a response to the user
            Examination::create($examinationObj);
        }

        if ($promoApplied) {
            $this->addPromoCodeLog($promoData, null, $promoCode, $currency, null, 'VIDEO');
        }

        $response = array_merge($charged, [
            'booking_id' => $booking['booking']['id'],
            'charge_id' => $charged['id'],
            'charge_description' => $charged['description']
        ]);

        if (isset($charged['error'])) {
            return response()->json($response, 400);
        } else {
            return response()->json($response, 200);
        }
    }

    public function video_confirm_payment(Request $request)
    {
        try {

            $makePlans = $this->shouldMakePlans();
            $intent = \Stripe\PaymentIntent::retrieve($request->input('payment_intent_id'));
            $response = $intent->confirm(); //  'payment_method' => 'card',

            // Payment complete
            if ($response->status === 'succeeded') {

                if ($makePlans) {
                    $this->updateBookingAfterPayment(
                        $request->input('booking_id'),
                        $request->input('charge_id'),
                        $request->input('charge_description'),
                        $request->input('region')
                    );
                }
            }

            return response()->json(['status' => $response->status], 200);
        } catch (Exception $ex) {
            Log::error($ex);
            var_dump($ex);
        }
    }

    public function video_checkpromo(Request $request)
    {
        $this->validate(
            $request,
            [
                'promoCode' => 'required',
                'type' => 'required',
                'currency' => 'required'
            ]
        );

        // To maintain consistency, converting the promocode to upper case.
        $promoCode = strtoupper($request->promoCode);
        // 'cs' for chat service and 'vs' for video service
        $type = $request->type;
        $user = null;
        $currency = $request->currency;

        return response()->json($this->promoCodeDetails($promoCode, $type, $user, $currency), 200);
        // Something went wrong with the fileupload
        // return reponse()->json(['error' => 'promo.failed'], 500);
    }

    /**
     * Book a video session for a logged in user
     *
     * NOTE - this should only be called by partner users.
     *
     * @param Request $request
     */
    public function userBookVideo(Request $request)
    {
        $makePlans = $this->shouldMakePlans();
        $this->validate(
            $request,
            [
                'appointment.id' => 'required',
                'appointment.free' => 'required|accepted',
                'appointment.timestamp' => 'required|date',
                'appointment.timestamp_end' => 'required|date',
                'appointment.available_resources' => 'required|array',
                'description' => 'required',
                'claim_uuid' => 'sometimes',
                'region' => 'required|in:co,no,se,uk,de'
            ]
        );

        // Get the user that is making the request
        $authUser = Auth::user();
        $user = User::findOrFail($authUser->uuid);
        $partnerAndDesc = $description = json_encode($request->input('description'));

        $claim_uuid = null;
        if ($user->partner) {
            $partnerAndDesc = 'Partner: ' . $user->partner . ' / Description: ' . $description;
            $claim_uuid_incoming =  $request->input('claim_uuid');
            if ($claim_uuid_incoming) {
                $claim = PartnerUserClaimNumber::findOrFail($claim_uuid_incoming);
                if ($claim && $claim->user_id == $user->uuid) {
                    $claim_uuid = $claim->uuid;
                }
            }
        }

        $resources = $request->input('appointment.available_resources');
        $appointment_timestamp = $request->input('appointment.timestamp');
        $region = $request->input('region');
        $service_id = config('constants.make_plans_map.' . $region . '.service_id');

        $appointment = array(
            'service_id' => $service_id,
            'resource_id' => $resources[array_rand($resources)],
            'booked_from' => $appointment_timestamp,
            'booked_to' => $request->input('appointment.timestamp_end'),
            'notes' => $partnerAndDesc,
            'person_attributes' => array(
                'name' => $user->firstname . ' ' . $user->lastname,
                'phonenumber' => $user->phonenumber
            )
        );

        Log::info('New booking: ' . print_r($appointment, true));
        Log::info('claim = ' . $claim_uuid);

        if ($makePlans) {
            $booking = $this->bookAppointment($appointment, $region, false); // do not require verification since this is a partner user
            if ($user->partner == 'Aurora') {
                try {
                    $appointment = array(
                        'appointment' => array(
                            "bookingId"   => $booking['booking']['id'],
                            "start"       => $booking['booking']['booked_from'],
                            "end"         => $booking['booking']['booked_to'],
                            "created_at"  => $booking['booking']['created_at'],
                            "updated_at"  => $booking['booking']['updated_at'],
                            "doctorName"  => $booking['booking']['resource']['title'],
                            "serviceName" => $booking['booking']['service']['title']
                        ),
                        'email' => $user->email
                    );
                    $url = env('AURORA_ENDPOINT');
                    $opts = array(
                        'http' => array(
                            'method'  => 'POST',
                            'header'  =>
                            "Authorization: Bearer" . " " . env('AURORA_API_KEY') . "\r\n" .
                                "Accept: application/json\r\n" .
                                "Content-Type: application/json\r\n" .
                                "User-Agent: php",
                            'content' => json_encode($appointment)
                        )
                    );
                    $ctx = stream_context_create($opts);
                    $res = file_get_contents($url, false, $ctx);
                } catch (Exception $ex) {
                    Log::error($ex);
                    throw $ex;
                }
            }
        } else {
            $booking['booking'] = [
                'id' => 'TEST',
                'booked_from' => 'test'
            ];
        }
        Log::info('Booking created: ' . $booking['booking']['id'] . ' ' . $booking['booking']['booked_from']);
        $response = ['booking_id' => $booking['booking']['id']];

        $dt = new \DateTime($appointment_timestamp);
        $deadline = 24; // always 24 hr deadline for partner users
        $deadline_time = $dt->modify('+' . $deadline . ' hour');

        // Create an examination so admin can send a response to the user
        $ex = Examination::create(
            [
                'patient' => $user->uuid,
                'category' => 'video',
                'other_description' => $description,
                'payment_type' => Examination::PAYMENT_TYPE_PARTNER,
                'partner_user_claim_number' => $claim_uuid
            ]
        );

        Log::info('New examination: ' . $ex->uuid);

        if ($user->partner && $ex && $booking) {
            if($user->partner === 'Aurora'){
                $variables = [
                    "name" => $user->lastname,
                    "email" => $user->email,
                    "created_time" => Carbon::parse($ex->created_at)->format('d/m/Y H:i'),
                    "start" => date("Y-m-d H:i:s", strtotime($booking['booking']['booked_from'])),
                    "end" => date("Y-m-d H:i:s", strtotime($booking['booking']['booked_to'])),
                ];
                Log::info($variables);
                $email_response = $this->sendMailViaMailjet("support@snapmed.no", env('AURORA_SUPPORT_MAIL'), (int) env('MJ_AURORA_USER_TEMPLATE_VIDEO'), $variables);
                if ($email_response->success()) {
                    Log::info('Email sent to ' . env('AURORA_SUPPORT_MAIL'));
                }else{
                    Log::info('Email failed for ' . env('AURORA_SUPPORT_MAIL'));
                    Log::error(print_r($email_response));
                }
               }
        }

        return response()->json($response,  200);
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
    // Internal helper functions

    // Wheter or not we should book appointments on makeplans PRODUCTION account
    private function shouldMakePlans()
    {
        return !app()->environment('local');
    }

    // Update the booking with the charge information
    private function updateBookingAfterPayment($booking_id, $charge_id, $charge_description, $region)
    {
        $this->updateBooking($booking_id, $charge_id, $charge_description, $region);
        // Confirm the booking once the charge has gone through
        $updBooking = $this->confirmAppointment($booking_id, $region);
        Log::info('Booking charged and confirmed: ' . json_encode($updBooking));
        // Confirmation sms is sent from make plans instead of us
        Log::info('- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - ');
    }


    private function bookAppointment($appointment, $region, $requireVerification = true)
    {
        $booking = array(
            'confirm' => $requireVerification ? 'false' : 'true', // confirm = false to require verification looks a bit weird but makedocs docs -> "If set to false then the 'initiate verification' event is executed. If verification is required the state will be set to `awaiting_verification`. If no verification is required then the state will be set to `awaiting_confirmation` or `confirmed`."
            'verification_send_email' => 'false',
            'verification_send_sms' => 'false',
            'confirmation_send_email' => 'false',
            'confirmation_send_sms' => $requireVerification ? 'false' : 'true',
            'booking' => $appointment
        );

        $api_key = config('constants.make_plans_map.' . $region . '.api_key');
        $user_agent = config('constants.make_plans_map.' . $region . '.user_agent');
        $booking_url = config('constants.make_plans_map.' . $region . '.booking_url');

        if (!isset($user_agent) || !isset($booking_url)) {
            return false;
        }

        try {
            $opts = array(
                'http' => array(
                    'method'  => 'POST',
                    'header'  =>
                    "Authorization: Basic ${api_key}\r\n" .
                        "Accept: application/json\r\n" .
                        "Content-Type: application/json\r\n" .
                        "User-Agent: " . $user_agent,
                    'content' => json_encode($booking)
                )
            );
            $ctx = stream_context_create($opts);

            $res = file_get_contents($booking_url, false, $ctx);
            return $this->parseJsonFromResponse($res, $http_response_header);
        } catch (Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }

    private function chargeAppointment(Request $request, $amount)
    {
        Log::info($request);

        // We don't do any validation of phone in this form, so to prevent Customer::create() from
        // failing with "Invalid string: xxxxxxxxxx; must be at most 20 characters" we just strip any
        // characters over 20
        $phone = $request->input('client.phonenumber');
        if (strlen($phone) > 20) {
            $phone = substr($phone, 0, 20);
        }

        $customer = Customer::create(
            [
                'name' => $request->input('client.name'),
                'email' => $request->input('client.email'),
                'phone' => $phone,
                'description' => 'Hudlegetime kunde: ' . $request->input('client.phonenumber'),
            ]
        );
        $payment = array(
            'payment_method' => $request->input('client.payment_method_id'),
            'payment_method_types' => ['card'],
            'amount' => $amount * 100,
            'currency' => $request->input('client.currency'),
            'customer' => $customer->id,
            'description' => 'Charged [' . $request->input('client.phonenumber') . '] for hudlegetime: ' . $request->input('appointment.timestamp'),
            'confirmation_method' => 'manual',
            'confirm' => true,
            'setup_future_usage' => 'off_session',
        );

        $intent = \Stripe\PaymentIntent::create($payment);
        Log::info('Appointment payment intent created: ' . json_encode($intent));

        if (($intent->status == 'requires_action' || $intent->status == 'requires_source_action') &&
            $intent->next_action->type == 'use_stripe_sdk'
        ) {
            # Tell the client to handle the action
            return [
                'requires_action' => true,
                'payment_intent_client_secret' => $intent->client_secret,
                'id' => $intent->id,
                'description' => $payment['description']
            ];
        } else if ($intent->status == 'succeeded') {
            # The payment didn’t need any additional actions and completed!
            # Handle post-payment fulfillment
            return [
                'success' => true,
                'id' => $intent->id,
                'description' => $payment['description']
            ];
        } else {
            # Invalid status
            return ['error' => $intent];
        }
    }

    private function updateBooking($bookingId, $charge_id, $charge_description, $region)
    {
        try {
            $api_key = config('constants.make_plans_map.' . $region . '.api_key');
            $user_agent = config('constants.make_plans_map.' . $region . '.user_agent');
            $booking_url = config('constants.make_plans_map.' . $region . '.booking_url');

            $booking = array(
                'booking' => array(
                    'custom_data' => array(
                        'charge' => $charge_id,
                        'charge_description' => $charge_description
                    )
                )
            );
            $opts = array(
                'http' => array(
                    'method'  => 'PUT',
                    'header'  =>
                    "Authorization: Basic ${api_key}\r\n" .
                        "Accept: application/json\r\n" .
                        "Content-Type: application/json\r\n" .
                        "User-Agent: " . $user_agent,
                    'content' => json_encode($booking)
                )
            );
            $ctx = stream_context_create($opts);
            $res = file_get_contents("${booking_url}/${bookingId}", false, $ctx);

            return $this->parseJsonFromResponse($res, $http_response_header);
        } catch (Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }

    private function confirmAppointment($bookingId, $region)
    {
        try {
            $content = array(
                'confirmation_send_sms' => TRUE
            );

            $api_key = config('constants.make_plans_map.' . $region . '.api_key');
            $user_agent = config('constants.make_plans_map.' . $region . '.user_agent');
            $booking_url = config('constants.make_plans_map.' . $region . '.booking_url');

            $opts = array(
                'http' => array(
                    'method'  => 'PUT',
                    'header'  =>
                    "Authorization: Basic ${api_key}\r\n" .
                        "Accept: application/json\r\n" .
                        "Content-Type: application/json\r\n" .
                        "User-Agent: " . $user_agent,
                    'content' => json_encode($content)
                )
            );
            $ctx = stream_context_create($opts);
            $res = file_get_contents("${booking_url}/${bookingId}/confirm", false, $ctx);

            return $this->parseJsonFromResponse($res, $http_response_header);
        } catch (Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }

    private function getAvailableSlots($dates, $region)
    {
        $api_key = config('constants.make_plans_map.' . $region . '.api_key');
        $user_agent = config('constants.make_plans_map.' . $region . '.user_agent');
        $url = config('constants.make_plans_map.' . $region . '.slot_url');

        if (!isset($user_agent) || !isset($url)) {
            return false;
        }

        try {
            $from = 'from=' . $dates['from_date'];
            $to = 'to=' . $dates['to_date'];
            $opts = array(
                'http' => array(
                    'method'  => 'GET',
                    'header'  =>
                    "Authorization: Basic ${api_key}\r\n" .
                        "Accept: application/json\r\n" .
                        "User-Agent: " . $user_agent,
                )
            );
            $ctx = stream_context_create($opts);
            $res = file_get_contents($url . "?${from}&${to}", false, $ctx);

            return $this->parseJsonFromResponse($res, $http_response_header);
        } catch (Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }

    private function getWeekYear($dateParam = 'NOW')
    {
        $dto = new DateTime($dateParam);
        $ret['week'] = $dto->format('W');
        $ret['year'] = $dto->format('Y');
        return $ret;
    }

    private function getStartAndEndDate($week, $year)
    {
        $dto = new DateTime();
        $dto->setISODate(intval($year), intval($week));
        $ret['from_date'] = $dto->format('Y-m-d');
        $dto->modify('+6 days');
        $ret['to_date'] = $dto->format('Y-m-d');
        return $ret;
    }

    private function parseJsonFromResponse($response, $responseHeaders)
    {
        if ($response !== false) {
            $serviceResult = false;
            $json = json_decode($response, true);

            foreach ($responseHeaders as $headerRow) {
                if (preg_match('/^HTTP\\/\\d\\.\\d (\\d{3}) (.*)$/', $headerRow, $matches)) {
                    $statusCode = $matches[1];
                    $serviceResult = ($statusCode >= 200 && $statusCode <= 299);
                }
            }

            if ($serviceResult && $json) {
                return $json;
            }
        }
        return false;
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - //
}
