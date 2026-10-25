<?php

return [
    'name' => env('APP_NAME', 'Kouhfar Dental Clinic'),

    'env' => env('APP_ENV', 'local'),

    'debug' => (bool) env('APP_DEBUG', true),

    'url' => env('APP_URL', 'http://localhost'),

    'timezone' => env('APP_TIMEZONE', 'Asia/Tehran'),

    'locale' => env('APP_LOCALE', 'fa'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'fa_IR'),

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    'maintenance' => [
        'driver' => 'file',
    ],

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],
];
