<?php

declare(strict_types=1);

return [
    'otp' => [
        'driver' => env('SMS_OTP_DRIVER', 'null'),
        // Explicit activation gate: live SMS must never become active accidentally because
        // a deployment secret or cached configuration was copied into a new environment.
        'live_sms_approved' => env('OTP_LIVE_SMS_APPROVED', false),
        // MeliPayamak's current OTP contract carries its auth token in the HTTPS URL path.
        // Production activation therefore also requires an explicit risk acceptance.
        'provider_url_path_risk_accepted' => env('OTP_PROVIDER_URL_PATH_RISK_ACCEPTED', false),
        'melipayamak' => [
            'token' => env('MELIPAYAMAK_OTP_TOKEN'),
        ],
    ],
    'sms' => [
        // Production capability is Kavenegar, but activation remains disabled until Task 15
        // supplies approved credentials/subscription. Tests and local development use log/null.
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
