<?php

declare(strict_types=1);

return [
    /*
     * Owner confirmed public bookings require mobile verification. Keep this true until Task 15
     * installs an approved provider + verification workflow. No SMS provider is selected/configured.
     */
    'phone_verification_required' => env('BOOKING_PHONE_VERIFICATION_REQUIRED', true),
];
