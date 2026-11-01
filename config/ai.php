<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('AI_ENABLED', false),
    'capabilities' => [
        'text' => [
            'enabled' => (bool) env('AI_TEXT_ENABLED', false),
            'provider' => env('AI_TEXT_PROVIDER', 'fake'),
            'model' => env('AI_TEXT_MODEL', 'fake-v1'),
            'timeout_seconds' => (int) env('AI_TEXT_TIMEOUT_SECONDS', 10),
            'fallback' => env('AI_TEXT_FALLBACK'),
        ],
        'image_analysis' => [
            'enabled' => (bool) env('AI_IMAGE_ANALYSIS_ENABLED', false),
            'provider' => env('AI_IMAGE_ANALYSIS_PROVIDER', 'fake'),
            'model' => env('AI_IMAGE_ANALYSIS_MODEL', 'fake-v1'),
            'timeout_seconds' => (int) env('AI_IMAGE_ANALYSIS_TIMEOUT_SECONDS', 15),
        ],
        'image_transformation' => [
            'enabled' => (bool) env('AI_IMAGE_TRANSFORMATION_ENABLED', false),
            'provider' => env('AI_IMAGE_TRANSFORMATION_PROVIDER', 'fake'),
            'model' => env('AI_IMAGE_TRANSFORMATION_MODEL', 'fake-v1'),
            'timeout_seconds' => (int) env('AI_IMAGE_TRANSFORMATION_TIMEOUT_SECONDS', 30),
        ],
    ],
    'rate_limits' => [
        'per_minute' => (int) env('AI_RATE_LIMIT_PER_MINUTE', 10),
        'daily_cost' => (float) env('AI_DAILY_COST_LIMIT', 0),
    ],
    'pricing' => [
        'input_per_1k' => (float) env('AI_INPUT_COST_PER_1K', 0),
        'output_per_1k' => (float) env('AI_OUTPUT_COST_PER_1K', 0),
    ],
    'allowed_context_keys' => ['locale', 'content_ids', 'feature_variant'],
];
