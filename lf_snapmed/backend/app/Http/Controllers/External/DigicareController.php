<?php

namespace App\Http\Controllers\External;

use App\Clinic;
use App\Examination;
use App\Http\Controllers\Controller;
use App\MedUser;
use App\Service;
use App\User;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class DigicareController extends Controller
{

    const MAKE_PLANS_MAP = array(
        'no' => array(
            'locale' => 'nb',
            'service_id' => 10821,
            'api_key' => 'Yjg4ZGIzOTg4YjZhOWNmZDJjNDg5ZDk5N2VmZGYxZTM6',
            'user_agent' => "Snapmed (https://snapmed.no)\r\n",
            'slot_url' => "https://snapmed.makeplans.no/services/10821/slots",
            'booking_url' => "https://snapmed.makeplans.no/api/v1/bookings",
            'timezone' => "Europe/Stockholm"
        ),
        'se' => array(
            'locale' => 'sv',
            'service_id' => 13650,
            'api_key' => 'ZDhiNmQ5MjNiZmVjZWI1NmMxZjNlM2E2YzVmYTFlMWM6',
            'user_agent' => "SnapmedSE (https://snapmed.se)\r\n",
            'slot_url' => "https://snapmedse.makeplans.net/services/13650/slots",
            'booking_url' => "https://snapmedse.makeplans.net/api/v1/bookings",
            'timezone' => "Europe/Stockholm"
        ),
        'uk' => array(
            'locale' => 'gb',
            'service_id' => 15101,
            'api_key' => 'MGEyNjQ1ZjRiN2JmY2E3YTlmYWMwNGQ1ZjY3Y2E0ZmIyMTc2Nzc1MQ==',
            'user_agent' => "Snapmed-UK (https://snapmed.co.uk)\r\n",
            'slot_url' => "https://snapmed-uk.makeplans.net/services/15101/slots",
            'booking_url' => "https://snapmed-uk.makeplans.net/api/v1/bookings",
            'timezone' => "Europe/London"
        ),
        'de' => array(
            'locale' => 'de',
            'service_id' => 15324,
            'api_key' => 'MjM1ZThhMzcyZmQ4MDQ3OTU0MWZkMDYxMWQxNmIzMTkyYjQ4MjU3NA==',
            'user_agent' => "Snapmed-DE (https://snapmed.de)\r\n",
            'slot_url' => "https://snapmed-de.makeplans.net/services/15324/slots",
            'booking_url' => "https://snapmed-de.makeplans.net/api/v1/bookings",
            'timezone' => "Europe/Berlin"
        ),
    );

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    { }

    /**
     * Return list of services for particular clinic.
     *
     * @param Request $request http request with clinic_id, from_date, to_date.
     *
     * @return http response with id, name, code, duration.
     */
    public function services(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'clinic_id' => 'required|string',
                'from_date' => 'required|date',
                'patient_ssn' => 'sometimes|string',
                'to_date' => 'required|date'
            ]
        );
        if ($validator->fails()) {
            return response()->json($validator->messages(), Response::HTTP_BAD_REQUEST);
        }

        $services = Service::where('clinic_id', $request->input('clinic_id'))->get();
        return response()->json($services, 200);
    }

    /**
     * Return list of doctors associated with particular clinic and service.
     *
     * @param Request $request http request with clinic_id, service_id, from_date, to_date.
     *
     * @return http response with id, name, profession, profession_code.
     */
    public function specialists(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'clinic_id' => 'required|string',
                'service_id' => 'required|string',
                'patient_ssn' => 'sometimes|string',
                'from_date' => 'required|date',
                'to_date' => 'required|date'
            ]
        );
        if ($validator->fails()) {
            return response()->json($validator->messages(), Response::HTTP_BAD_REQUEST);
        }
        $medUsersId = json_decode(Service::where(['clinic_id' => $request->input('clinic_id'), 'id' => $request->input('service_id')])->first()->med_users);

        $medUsers = MedUser::select('uuid as id', 'display_name as name', 'profession', 'profession_code')->whereIn('uuid', $medUsersId)->get();
        $medUsers = $medUsers->makeVisible(['uuid']);

        return response()->json($medUsers, 200);
    }

    /**
     * Return booking timeslots for particular doctor.
     *
     * @param Request $request http request with clinic_id, service_id, specialist_id, from_date, to_date.
     *
     * @return http response with time_from, time_to, booking_token.
     */
    public function timeslots(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'clinic_id' => 'required|string',
                'service_id' => 'required|string',
                'specialist_id' => 'required|string',
                'patient_ssn' => 'sometimes|string',
                'from_date' => 'required|date',
                'to_date' => 'required|date'
            ]
        );
        if ($validator->fails()) {
            return response()->json($validator->messages(), Response::HTTP_BAD_REQUEST);
        }

        $makeplansResourceId = MedUser::where('uuid', $request->input('specialist_id'))->first()->makeplans_resource_id;
        $country = Clinic::where('id', $request->input('clinic_id'))->first()->country;

        $api_key = self::MAKE_PLANS_MAP[$country]['api_key'];
        $user_agent = self::MAKE_PLANS_MAP[$country]['user_agent'];
        $slot_url = self::MAKE_PLANS_MAP[$country]['slot_url'];

        $fromDate = Carbon::parse($request->input('from_date'), 'UTC')->tz(self::MAKE_PLANS_MAP[$country]['timezone'])->format('Y-m-d');
        $toDate = Carbon::parse($request->input('to_date'), 'UTC')->tz(self::MAKE_PLANS_MAP[$country]['timezone'])->format('Y-m-d');

        try {
            $from = 'from=' . $fromDate;
            $to = 'to=' . $toDate;
            $selected_resources = 'selected_resources=' . $makeplansResourceId;
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
            $res = file_get_contents($slot_url . "?${from}&${to}&${selected_resources}", false, $ctx);

            $makeplanSlots = $this->parseJsonFromResponse($res, $http_response_header);

            if (!$makeplanSlots) {
                return response()->json([], 200);
            }
            $slots = [];
            foreach ($makeplanSlots as $makeplanSlot) {
                $appointment = array(
                    'service_id' => $slot_url = self::MAKE_PLANS_MAP[$country]['service_id'],
                    'resource_id' => $makeplansResourceId,
                    'booked_from' => $makeplanSlot['timestamp'],
                    'booked_to' => $makeplanSlot['timestamp_end']
                );
                $data['appointment'] = $appointment;
                $data['country'] = $country;
                $slots[] = [
                    'time_from' => Carbon::parse($makeplanSlot['timestamp'], self::MAKE_PLANS_MAP[$country]['timezone'])->tz('UTC')->format('Y-m-d\TH:i:s.v0'),
                    'time_to' => Carbon::parse($makeplanSlot['timestamp_end'], self::MAKE_PLANS_MAP[$country]['timezone'])->tz('UTC')->format('Y-m-d\TH:i:s.v0'),
                    'booking_token' => $this->jwt($data)
                ];
            }
            return response()->json($slots, 200);
        } catch (Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }

    /**
     * Book an appointment with doctor and patient.
     *
     * @param Request $request http request with booking_token, patient_data.
     *
     * @return http response with jwt booking_token, patient_data.
     */
    public function bookings(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'booking_token' => 'required|string',
                'patient_data.ssn' => 'required|string',
                'patient_data.first_name' => 'required|string',
                'patient_data.last_name' => 'required|string',
                'patient_data.address' => 'required|string',
                'patient_data.post_code' => 'required|string',
                'patient_data.email' => 'required|email',
                'patient_data.phone_number' => 'required|string',
                'patient_data.mobile_number' => 'required|string',
                'patient_data.gender' => 'required|string',
                'sakid' => 'sometimes|string'
            ]
        );
        if ($validator->fails()) {
            return response()->json($validator->messages(), Response::HTTP_BAD_REQUEST);
        }
        $creds = JWT::decode($request->input('booking_token'), env('JWT_SECRET'), ['HS256']);
        $appointment = $creds->sub->appointment;
        $appointment->notes = 'Booking from digicare partner';
        $appointment->person_attributes = array(
            'name' => $request->input('patient_data.first_name') . ' ' . $request->input('patient_data.last_name'),
            'email' => $request->input('patient_data.email'),
            'phonenumber' => $request->input('patient_data.mobile_number')
        );
        $country = $creds->sub->country;
        $appointment = json_decode(json_encode($appointment), true);
        $booking = $this->bookAppointment($appointment, $country, false); // do not require verification since this is a partner user

        $hashedSsn = sha1($request->input('patient_data.ssn'));
        $encryptedSsn = Crypt::encryptString($request->input('patient_data.ssn'));
        $user = User::where('ssn_hash', $hashedSsn)->first();
        if (!$user) {
            $user = User::create(
                [
                    'ssn' => $encryptedSsn,
                    'ssn_hash' => $hashedSsn,
                    'phonenumber' => $request->input('patient_data.mobile_number'),
                    'email' => $request->input('patient_data.email'),
                    'firstname' => $request->input('patient_data.first_name'),
                    'lastname' => $request->input('patient_data.last_name'),
                    'locale' => self::MAKE_PLANS_MAP[$country]['locale'],
                    'region' => $country,
                    'password' => app('hash')->make(str_random(20)),    // generate a random password for this user (never used since they will use bankID to login)
                    'login_method' => User::LOGIN_METHOD_PHONE,
                    'partner' => 'Digicare'
                ]
            );
        }
        $dt = new \DateTime($appointment['booked_from']);
        $deadline = 24; // always 24 hr deadline for partner users
        $deadline_time = $dt->modify('+' . $deadline . ' hour');
        $gender = '';

        if ($request->input('patient_data.gender') == 'M') {
            $gender = 'man';
        } else if ($request->input('patient_data.gender') == 'F') {
            $gender = 'woman';
        }
        $examinationObj = [
            'patient' => $user->uuid,
            'gender' => $gender,
            'deadline' => $deadline,
            'deadline_time' => $deadline_time,
            'category' => 'video',
            'other_description' => 'Digicare partner',
            'payment_type' => Examination::PAYMENT_TYPE_PARTNER,
            'case_code' => $request->input('sakid')
        ];

        Examination::create($examinationObj);
        return response()->json($request->all(), 200);
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

    private function jwt($data)
    {
        $payload = [
            'iss' => "snapmed",
            'aud' => "digicare",
            'sub' => $data,
            'iat' => time(),
        ];

        return JWT::encode($payload, env('JWT_SECRET'));
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

        $api_key = self::MAKE_PLANS_MAP[$region]['api_key'];
        $user_agent = self::MAKE_PLANS_MAP[$region]['user_agent'];
        $booking_url = self::MAKE_PLANS_MAP[$region]['booking_url'];

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
}
