<?php

declare(strict_types=1);

return [
    'otp' => [
        'driver' => env('SMS_OTP_DRIVER', 'null'),
        'live_sms_approved' => env('OTP_LIVE_SMS_APPROVED', false),
        'provider_url_path_risk_accepted' => env('OTP_PROVIDER_URL_PATH_RISK_ACCEPTED', false),
        'university_demo_mode' => env('UNIVERSITY_DEMO_MODE', false),
        'demo_code' => env('OTP_DEMO_CODE', '123456'),
        'melipayamak' => [
            'token' => env('MELIPAYAMAK_OTP_TOKEN'),
        ],
    ],
    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'timezone' => 'Asia/Tehran',
        'quiet_hours' => [
            'start' => '22:00',
            'release' => '08:01',
        ],
        'clinic_address' => 'شهرری، خیابان ۲۴ متری، نبش خیابان نفری، ساختمان عقیق، طبقه ۲، واحد ۸',
        'clinic_map_url' => 'https://maps.app.goo.gl/rQddN7iHYZitX6b27',
        'kavenegar' => [
            'api_key' => env('KAVENEGAR_API_KEY'),
            'sender' => env('KAVENEGAR_SENDER'),
            'endpoint' => env('KAVENEGAR_ENDPOINT', 'https://api.kavenegar.com/v1'),
        ],
    ],
];
