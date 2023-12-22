<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Log;
use App\User;
use App\PromoCode;
use App\PromoCodeLog;
use App\Notifications\SmsSender;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Hash;
use \Mailjet\Resources;

class Controller extends BaseController
{
    public function __construct()
    { }

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
    protected function sendMailViaMailjet($from, $to, $templateID, $variables)
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
     * Sends one-time-password to user via twillio.
     *
     * @param String $phonenumber The phonenumber to send to.
     * @param String $otp         The otp to send to the user.
     *
     * @return void
     */
    protected function sendOtpToUser($phonenumber, $otp, $lang = 'nb', $region)
    {
        $messages = array(
            'nb' => "Din engangskode er: $otp",
            'en' => "Your one time code is: $otp",
            'sv' => "Din engångskod är: $otp",
            'de' => "Ihr einmaliger Code ist: $otp",
            'gb' => "Your one time code is: $otp"
        );
        try {
            SmsSender::sendSms($phonenumber, $messages[$lang], $region);
        } catch (Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
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
        // $codeAlphabet = "ABCDEFGHJKLMNPQRSTUVWXYZ";
        // $codeAlphabet.= "abcdefghijkmnopqrstuvwxyz";
        // $codeAlphabet.= "123456789";
        $codeAlphabet = "0123456789";
        $max = strlen($codeAlphabet);

        if (env('APP_ENV') === 'local') {
            return '1234';
        }

        for ($i = 0; $i < $length; $i++) {
            $token .= $codeAlphabet[$this->cryptoRandSecure(0, $max - 1)];
        }

        return $token;
    }

    /**
     * The function verifies the password and then generates a OTP code that is sent to the user.
     *
     * @param User   $user     The user in question.
     * @param String $password The password to verify.
     *
     * @return boolean based on wether password is correct or not.
     */
    protected function attemptLogin($user, $password, $override_phonenumber = FALSE)
    {
        // Is the password correct
        if (Hash::check($password, $user->password)) {
            // Generate one-time-password
            $otp = $this->getToken(4);

            if($user->phonenumber) {
                $phonenumber = $user->phonenumber;
            } else if ($user->phonenumber_not_verified) {
                $phonenumber = $user->phonenumber_not_verified;
            }

            if ($override_phonenumber) {
                $this->sendOtpToUser($override_phonenumber, $otp, $user->locale, $user->region);
            } else {
                $this->sendOtpToUser($phonenumber, $otp, $user->locale, $user->region);
            }
            // Encrypt the password and save it.
            $user->otp = app('hash')->make($otp);
            $user->otp_failed_count = 0; // reset failed count here since this is a successful login
            $user->save();
            return true;
        }
        $start = 'tok_';
        // Is this an existing customer and are they trying to reset password their password?
        if (substr($password, 0, strlen($start)) === $start) {
            // Generate one-time-password
            $otp = $this->getToken(4);
            $this->sendOtpToUser($user->phonenumber, $otp, $user->locale, $user->region);
            // Encrypt the password and save it.
            $user->otp = app('hash')->make($otp);
            $user->save();
            return true;
        }
        return false;
    }

    /**
     * The function adds a log for the promocode usage to the promo_code_logs table
     *
     * @param promoData   $promoData     General promocode details.
     * @param user $user The user that added the promocode.
     * @param promoCode $promoCode The actual promocode.
     * @param currency $currency The current for the final amount.
     * @param deadline $deadline The deadline chosen for chat.
     * @param type $user Type of the service (CHAT/VIDEO).
     *
     * @return null
     */
    protected function addPromoCodeLog($promoData, $user, $promoCode, $currency, $deadline = null, $type = 'CHAT')
    {
        if ($user) {
            $uuid = $user->uuid;
        } else {
            $uuid = null;
        }

        if ($deadline) {
            $deadline_chat = $deadline;
        } else {
            $deadline_chat = 0.00;
        }
        $customer = PromoCodeLog::create(
            [
                'user_id' => $uuid,
                'promo_id' => $promoData['promoId'],
                'promocode' => $promoCode,
                'discount_value' => $promoData['discount'],
                'discount_fixed' => $promoData['discountFixed'],
                'initial_amount' => $promoData['initialAmount'],
                'amount_charged' => $promoData['amount'],
                'currency' => $currency,
                'service' => $type,
                'deadline_chat' => $deadline_chat
            ]
        );
    }

    /**
     * The function that checks the promo code usability.
     *
     * @param promoCodeEntry   $promoCodeEntry     promocode object from the database.
     * @param user $user The used that added the promocode.
     *
     * @return boolean true if the code is used, false otherwise
     */
    protected function checkPromoCodeUsability($promoCodeEntry, $user)
    {
        // If promocode is always reusable just return false for used
        if ($promoCodeEntry->reusable) {
            return false;
        }
        // If the promo code is a one time code then check if it has been used by anyone i.e. an entry exists in the log
        elseif ($promoCodeEntry->one_time_code) {
            $promoCodeLog = PromoCodeLog::where('promo_id', $promoCodeEntry->uuid)->first();
            if ($promoCodeLog) {
                return true;
            } else {
                return false;
            }
        }
        // If the promo code is not reusable then check if the same user has used the promo code before
        elseif (!$promoCodeEntry->reusable and $user) {
            $promoCodeLog = PromoCodeLog::where('promo_id', $promoCodeEntry->uuid)->where('user_id', $user->uuid)->first();
            if ($promoCodeLog) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * The function that checks the promo code usability.
     *
     * @param user $user The user that added the promocode.
     * @param promoCode $promoCode The actual promocode.
     * @param type $user Type of the service (cs/vs).
     * @param return_promo_id $return_promo_id This is so that we can save the promocode id in the logs.
     *
     * @return result promocode details
     */

    protected function promoCodeDetails($promoCode, $type, $user, $currency, $return_promo_id = false)
    {
        $result = [];
        $result['exists'] = false;
        // $promoCodeEntry = PromoCode::where('promocode', $promoCode)->where('applicable_currencies', 'LIKE', '%%'.$currency.'%')->orderBy('updated_at')->first();
        $promoCodeEntry = PromoCode::where('promocode', $promoCode)
            ->where(function ($q) use ($currency) {
                $q->where('applicable_currencies', 'LIKE', '%' . $currency . '%')
                    ->orWhere('applicable_currencies', 'LIKE', '%ALL%');
            })->orderBy('updated_at')->first();

        if ($promoCodeEntry) {
            $result['exists'] = true;

            // Check if the promo is enabled(valid) or has been disabled.
            $result['valid'] = $promoCodeEntry->enabled;

            // If the promo code is valid, then check if it satisfies the date conditions

            if ($result['valid']) {
                // If both valid from and to exists then check if current date lies in the range
                if ($promoCodeEntry->valid_from && $promoCodeEntry->valid_to) {
                    $current_date = date('Y-m-d H:i:s');
                    if ($current_date >= $promoCodeEntry->valid_from && $current_date <= $promoCodeEntry->valid_to) {
                        $result['valid'] = true;
                    } else {
                        $result['valid'] = false;
                    }
                }
                //If only valid from exists then check if current date is after valid from
                elseif ($promoCodeEntry->valid_from) {
                    $current_date = date('Y-m-d H:i:s');
                    if ($current_date >= $promoCodeEntry->valid_from) {
                        $result['valid'] = true;
                    } else {
                        $result['valid'] = false;
                    }
                }
                //If only valid to exists then check if current date if before valid to
                elseif ($promoCodeEntry->valid_to) {
                    $current_date = date('Y-m-d H:i:s');
                    if ($current_date <= $promoCodeEntry->valid_to) {
                        $result['valid'] = true;
                    } else {
                        $result['valid'] = false;
                    }
                }
                // Otherwise the code is always valid
                else {
                    $result['valid'] = true;
                }

                // If the promocode also satisfies the dates range validity then we check if it has been used before
                if ($result['valid']) {
                    $result['used'] = $this->checkPromoCodeUsability($promoCodeEntry, $user);

                    if (!$result['used']) {
                        if ($type == 'cs') {   //cs stands for chat system. vs stands for video system
                            $result['discount12hrs'] = $promoCodeEntry->discount_12hrs;
                            $result['discount24hrs'] = $promoCodeEntry->discount_24hrs;

                            // if the discountPercent value is none then the discount code is not applicable
                            if ($result['discount12hrs'] || $result['discount24hrs']) {
                                $result['applicable'] = true;
                                $result['discountFixed'] = $promoCodeEntry->discount_fixed;
                            } else {
                                $result['applicable'] = false;
                            }
                        } elseif ($type == 'vs') {
                            $result['discountVideo'] = $promoCodeEntry->discount_video;

                            // if the discountPercent value is none then the discount code is not applicable
                            if ($result['discountVideo']) {
                                $result['applicable'] = true;
                                $result['discountFixed'] = $promoCodeEntry->discount_fixed;
                            } else {
                                $result['applicable'] = false;
                            }
                        } else {
                            $result['applicable'] = false;
                        }
                    }
                }
                // if the discount code is valid then return the percentages involved
            }

            if ($return_promo_id) {
                $result['promoId'] = $promoCodeEntry->uuid;
            }
        }
        return $result;
    }

    /**
     * The function that checks the promo code usability.
     *
     * @param amount $amount The initial amount of the service.
     * @param promoCode $promoCode The actual promocode.
     * @param deadline $deadline Deadline for chat.
     * @param user $user The user that added the promocode.
     * @param type $user Type of the service (cs/vs).
     *
     * @return data recalculated amount, discount and other promo related data
     */
    protected function recalculateAmountWithPromo($amount, $promoCode, $deadline, $user, $currency, $type = 'cs')
    {
        $data = [];
        $data['amount'] = $amount;
        $data['promoApplied'] = false;

        $result = $this->promoCodeDetails($promoCode, $type, $user, $currency, true);
        Log::info('Promo code details: ' . json_encode($result));
        if ($result['exists'] && $result['valid'] && !$result['used'] && $result['applicable']) {
            if ($type == 'cs') {
                if ($deadline == 12) {
                    $discount = $result['discount12hrs'];
                } else {
                    $discount = $result['discount24hrs'];
                }
            } else {
                $discount = $result['discountVideo'];
            }

            $data['promoApplied'] = true;
            $data['initialAmount'] = $amount;
            $data['discountFixed'] = $result['discountFixed'];

            // Depending on discountFixed flag, we decide we deduct the value or the percentage value
            if ($data['discountFixed']) {
                $discountAmount = $discount;
            } else {
                $discountAmount = $amount * $discount / 100;
            }
            $amount = $amount - $discountAmount;

            $data['amount'] = $amount;
            $data['discount'] = $discount;
            $data['promoId'] = $result['promoId'];
        }

        return $data;
    }

    /**
     * Create a new token.
     *
     * @param User $user the user to create a new JWT token.
     *
     * @return string jwt token for the authenticated user.
     */
    protected function jwt(User $user)
    {
        $payload = [
            'iss' => "snapmed", // Issuer of the token
            'sub' => $user->uuid, // Subject of the token
            'iat' => time(), // Time when JWT was issued.
            'exp' => time() + 60 * 60 // Expiration time
        ];

        // As you can see we are passing `JWT_SECRET` as the second parameter that will
        // be used to decode the token in the future.
        return JWT::encode($payload, env('JWT_SECRET'));
    }
}
