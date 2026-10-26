<?php

declare(strict_types=1);

return [
    'login_throttle_attempts' => (int) env('STAFF_LOGIN_THROTTLE_ATTEMPTS', 5),
    'login_throttle_seconds' => (int) env('STAFF_LOGIN_THROTTLE_SECONDS', 60),
    // Timeout values are owner-controlled and remain disabled until approved.
    'idle_timeout_minutes' => env('STAFF_IDLE_TIMEOUT_MINUTES') !== null ? (int) env('STAFF_IDLE_TIMEOUT_MINUTES') : null,
    'absolute_timeout_minutes' => env('STAFF_ABSOLUTE_TIMEOUT_MINUTES') !== null ? (int) env('STAFF_ABSOLUTE_TIMEOUT_MINUTES') : null,
];
