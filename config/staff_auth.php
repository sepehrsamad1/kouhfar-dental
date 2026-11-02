<?php

declare(strict_types=1);

return [
    'login_throttle_attempts' => (int) env('STAFF_LOGIN_THROTTLE_ATTEMPTS', 5),
    'login_throttle_seconds' => (int) env('STAFF_LOGIN_THROTTLE_SECONDS', 60),
    // Clinic-owner approved: 15-minute idle and 8-hour absolute staff session limits.
    'idle_timeout_minutes' => (int) env('STAFF_IDLE_TIMEOUT_MINUTES', 15),
    'absolute_timeout_minutes' => (int) env('STAFF_ABSOLUTE_TIMEOUT_MINUTES', 480),
];
