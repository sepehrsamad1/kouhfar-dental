<?php

$localEnvironment = in_array(env('APP_ENV', 'production'), ['local', 'testing'], true);
$cookieName = env('SESSION_COOKIE', 'kouhfar-session');

if (! $localEnvironment && ! str_starts_with($cookieName, '__Secure-')) {
    $cookieName = '__Secure-'.$cookieName;
}

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
    'cookie' => $cookieName,
    'path' => '/',
    'domain' => env('SESSION_DOMAIN'),
    'secure' => $localEnvironment ? env('SESSION_SECURE_COOKIE', false) : true,
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
];
