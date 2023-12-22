<?php

namespace App\Notifications;

class SmsSender
{

    function __construct()
    { }

    const TWILIO_NUMBER = array(
        'co' => '+14153159060',
        'no' => '+4759446933',
        'uk' => '+447588062111',
        'se' => '+4759446933',
        'de' => '+14153159060'
    );

    // const TWILIO_NUMBER = array(
    //         'co' => 'SNAPMED',
    //         'no' => 'SNAPMED',
    //         'uk' => 'SNAPMED',
    //         'se' => 'SNAPMED',
    //         'de' => 'SNAPMED'
    //     );

    public static function sendSms($phonenumber, $message, $region)
    {
        try {
            $region = ($region != null) ? $region : 'co';
            $fromNumber = self::TWILIO_NUMBER[$region];
            if (env('APP_ENV') === 'local') {
                $fromNumber = '+15005550006';
            }
            app('twilio')->messages->create(
                $phonenumber,
                [
                    'from' => $fromNumber,
                    'body' => $message
                ]
            );
        } catch (Exception $ex) {
            Log::error($ex);
            throw $ex;
        }
    }
}
