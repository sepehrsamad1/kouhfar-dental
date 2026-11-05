<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('SMART_SMILE_ENABLED', false),
    'release_gate' => (bool) env('SMART_SMILE_RELEASE_GATE', false),
    'consent_version' => 'v1',
    'consent_scope' => 'smart-smile-image-analysis',
    'retention_days' => 90,
    'token_ttl_hours' => 24,
    'result_retention_days' => 365,
    'max_uploads_per_consent' => 1,
];
