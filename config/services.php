<?php

return [
    'avalai' => [
        'api_key' => env('AVALAI_API_KEY'),
        'base_url' => env('AVALAI_BASE_URL', 'https://api.avalai.ir/v1'),
        'model' => env('AVALAI_MODEL'),
        'timeout' => (int) env('AVALAI_TIMEOUT', 30),
    ],
];
