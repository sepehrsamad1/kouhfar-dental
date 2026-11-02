<?php

declare(strict_types=1);

return [
    'provider_version' => env('AI_PROVIDER_VERSION', 'fake-contract-v1'),
    'enabled' => (bool) env('AI_ENABLED', false),
    'capabilities' => [
        'text' => [
            'enabled' => (bool) env('AI_TEXT_ENABLED', false),
            'provider' => env('AI_TEXT_PROVIDER', 'fake'),
            'model' => env('AI_TEXT_MODEL', 'fake-v1'),
            'model_version' => env('AI_TEXT_MODEL_VERSION', env('AI_TEXT_MODEL', 'fake-v1')),
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
    'pricing_irr' => [
        'text' => [
            'input_per_1k' => (float) env('AI_TEXT_INPUT_COST_IRR_PER_1K', 0),
            'output_per_1k' => (float) env('AI_TEXT_OUTPUT_COST_IRR_PER_1K', 0),
        ],
        'image_analysis' => [
            'input_per_1k' => (float) env('AI_IMAGE_INPUT_COST_IRR_PER_1K', 0),
            'output_per_1k' => (float) env('AI_IMAGE_OUTPUT_COST_IRR_PER_1K', 0),
        ],
        'image_transformation' => [
            'input_per_1k' => (float) env('AI_IMAGE_TRANSFORM_INPUT_COST_IRR_PER_1K', 0),
            'output_per_1k' => (float) env('AI_IMAGE_TRANSFORM_OUTPUT_COST_IRR_PER_1K', 0),
        ],
    ],
    'allowed_context_keys' => ['locale', 'content_ids', 'feature_variant'],
];
