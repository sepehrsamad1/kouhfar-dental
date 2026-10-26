<?php

return [
    'defaults' => [
        'guard' => 'staff',
        'passwords' => 'staff',
    ],
    'guards' => [
        'staff' => [
            'driver' => 'session',
            'provider' => 'staff',
        ],
    ],
    'providers' => [
        'staff' => [
            'driver' => 'eloquent',
            'model' => App\StaffUser::class,
        ],
    ],
    'passwords' => [
        'staff' => [
            'provider' => 'staff',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],
    'password_timeout' => 10800,
];
