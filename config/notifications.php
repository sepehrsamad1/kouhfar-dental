<?php

declare(strict_types=1);

return [
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
