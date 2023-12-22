<?php

$regionKeys = array('co', 'no', 'se', 'uk', 'de');

return [

    'partner_stb' => 'Storebrand',

    'currency_map' => array(
        'NOK' => array('Newuser' => 495, 'Returninguser' => 395),
        'SEK' => array('Newuser' => 395, 'Returninguser' => 345)
    ),

    'make_plans_hour_price_map' => array(
        'NOK' => 895,
        'SEK' => 795
    ),

    'make_plans_map' => array(
        'no' => array(
            'service_id' => 10821,
            'api_key' => 'Yjg4ZGIzOTg4YjZhOWNmZDJjNDg5ZDk5N2VmZGYxZTM6',
            'user_agent' => "Snapmed (https://snapmed.no)\r\n",
            'slot_url' => "https://snapmed.makeplans.no/api/v1/services/10821/slots",
            'booking_url' => "https://snapmed.makeplans.no/api/v1/bookings"
        ),
        'se' => array(
            'service_id' => 13650,
            'api_key' => 'ZDhiNmQ5MjNiZmVjZWI1NmMxZjNlM2E2YzVmYTFlMWM6',
            'user_agent' => "SnapmedSE (https://snapmed.se)\r\n",
            'slot_url' => "https://snapmedse.makeplans.net/api/v1/services/13650/slots",
            'booking_url' => "https://snapmedse.makeplans.net/api/v1/bookings"
        ),
        'uk' => array(
            'service_id' => 15101,
            'api_key' => 'MGEyNjQ1ZjRiN2JmY2E3YTlmYWMwNGQ1ZjY3Y2E0ZmIyMTc2Nzc1MQ==',
            'user_agent' => "Snapmed-UK (https://snapmed.co.uk)\r\n",
            'slot_url' => "https://snapmed-uk.makeplans.net/api/v1/services/15101/slots",
            'booking_url' => "https://snapmed-uk.makeplans.net/api/v1/bookings"
        ),
        'de' => array(
            'service_id' => 15324,
            'api_key' => 'MjM1ZThhMzcyZmQ4MDQ3OTU0MWZkMDYxMWQxNmIzMTkyYjQ4MjU3NA==',
            'user_agent' => "Snapmed-DE (https://snapmed.de)\r\n",
            'slot_url' => "https://snapmed-de.makeplans.net/api/v1/services/15324/slots",
            'booking_url' => "https://snapmed-de.makeplans.net/api/v1/bookings"
        ),
    ),

    'email_verify_msg' => array(
        'nb' => array('line_1' => 'Takk for at du bruker Dr. Dropin Hud.', 'line_2' => 'Vennligst bekreft din epost ved å klikke på knappen.'),
        'sv' => array('line_1' => 'Tack för att du använder Dr. Dropin Hud.', 'line_2' => 'Vänligen klicka här för att bekräfta din email.'),
    ),

    'bank_id_return_url' => [
        'flow' => [
            'production' => [
                'no' => 'https://drdropin.snapmed.no/bankid-return',
                'se' => 'https://drdropin.snapmed.se/bankid-return',
            ],
    
            'test' => [
                'no' => 'https://qa.app.snapmed.no/bankid-return',
                'se' => 'https://qa.app.snapmed.se/bankid-return',
            ],
            'local' => [
                'no' => 'http://localhost:' . env('FLOW_PORT') . '/bankid-return',
                'se' => 'http://localhost:' . env('FLOW_PORT') . '/bankid-return',
            ],
        ],
        'dashboard' => [
            'production' => [
                'no' => 'https://response.drdropin.snapmed.no/bankid-return',
                'se' => 'https://response.drdropin.snapmed.se/bankid-return',
            ],
    
            'test' => [
                'no' => 'https://qa.response.snapmed.no/bankid-return',
                'se' => 'https://qa.response.snapmed.se/bankid-return',
            ],
            'local' => [
                'no' => 'http://localhost:' . env('DASHBOARD_PORT') . '/bankid-return',
                'se' => 'http://localhost:' . env('DASHBOARD_PORT') . '/bankid-return',
            ],
        ]
    ],

    'patient_password_reset_url' => [
        'production' => [
            'no' => 'https://drdropin.snapmed.no/update-password',
            'se' => 'https://drdropin.snapmed.se/update-password',
        ],

        'test' => [
            'no' => 'https://qa.app.snapmed.no/update-password',
            'se' => 'https://qa.app.snapmed.se/update-password',
        ],

        'local' => array_fill_keys($regionKeys, 'http://localhost:' . env('FLOW_PORT') . '/update-password')

    ],

    'patient_verification_url' => [
        'production' => [
            'no' => 'https://drdropin.snapmed.no/email-verification',
            'se' => 'https://drdropin.snapmed.se/email-verification',
        ],

        'test' => [
            'no' => 'https://qa.app.snapmed.no/email-verification',
            'se' => 'https://qa.app.snapmed.se/email-verification'
        ],

        'local' => array_fill_keys($regionKeys, 'http://localhost:' . env('FLOW_PORT') . '/email-verification')

    ],

    'login_redirect_url' => [
        'production' => [
            'no' => 'https://response.drdropin.snapmed.no',
            'se' => 'https://response.drdropin.snapmed.se',
        ],

        'test' => [
            'no' => 'https://qa.response.snapmed.no',
            'se' => 'https://qa.response.snapmed.se',
        ],

        'local' => array_fill_keys($regionKeys, 'http://localhost:' . env('DASHBOARD_PORT') . '/response')
    ],

    'doctor_password_reset_url' => [
        'production' => 'https://med.snapmed.no/update-password',
        'test'       => 'https://qa.med.snapmed.no/update-password',
        'local'      => 'http://localhost:' . env('PORT') . '/update-password'
    ],

    'doctor_onboard_url' => [
        'production' => 'https://med.snapmed.no/meduser/verify',
        'test'       => 'https://qa.med.snapmed.no/meduser/verify',
        'local'      => 'http://localhost:' . env('PORT') . '/meduser/verify'
    ]
];
