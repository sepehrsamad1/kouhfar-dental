<?php

return [
    'driver' => env('SESSION_DRIVER', 'file'),
    'lifetime' => (int) env('SESSION_LIFETIME', 120),
    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),
    'encrypt' => env('SESSION_ENCRYPT', false),
    'files' => storage_path('framework/sessions'),
    'connection' => env('SESSION_CONNECTION'),
    'table' => env('SESSION_TABLE', 'sessions'),
    'store' => env('SESSION_STORE'),
    'lottery' => [2, 100],
    'cookie' => env('SESSION_COOKIE', 'kouhfar-session'),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN'),
    'secure' => in_array(env('APP_ENV', 'production'), ['local', 'testing'], true)
        ? env('SESSION_SECURE_COOKIE', false)
        : true,
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
];
