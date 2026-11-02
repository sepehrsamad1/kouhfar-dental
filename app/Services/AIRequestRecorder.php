<?php

declare(strict_types=1);

namespace App\Services;

use App\AIRequest;

final class AIRequestRecorder
{
    /** @param array<int, string> $contentIds */
    public function record(
        string $feature,
        string $status,
        array $contentIds = [],
        string $provider = 'fake',
        string $model = 'fake-v1',
        string $capability = 'text',
        int $inputTokens = 0,
        int $outputTokens = 0,
        float $estimatedCostIrr = 0,
        ?float $actualCostIrr = null,
    ): void {
        AIRequest::query()->create([
            'feature' => $feature,
            'actor_type' => 'system',
            'actor_id' => null,
            'status' => $status,
            'created_at' => now('UTC')->toImmutable(),
            'content_ids' => array_values(array_unique(array_slice($contentIds, 0, 20))),
            'provider' => $provider,
            'model' => $model,
            'capability' => $capability,
            'input_tokens' => max(0, $inputTokens),
            'output_tokens' => max(0, $outputTokens),
            'estimated_cost_irr' => max(0, $estimatedCostIrr),
            'actual_cost_irr' => $actualCostIrr === null ? null : max(0, $actualCostIrr),
        ]);
    }
}
