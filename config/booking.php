<?php

declare(strict_types=1);

return [
    /*
     * Public bookings require phone verification. Keep enabled unless the clinic owner changes
     * the policy explicitly; provider activation is a separate deployment approval.
     */
    'phone_verification_required' => env('BOOKING_PHONE_VERIFICATION_REQUIRED', true),

    'otp' => [
        'ttl_minutes' => 5,
        'max_attempts' => 5,
        'resend_cooldown_seconds' => 60,
        'phone_send_limit_15_minutes' => 3,
        'phone_send_limit_1_hour' => 5,
        'ip_send_limit_1_hour' => 10,
    ],
];
