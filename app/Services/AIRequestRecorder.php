<?php

declare(strict_types=1);

namespace App\Services;

use App\AIRequest;

final class AIRequestRecorder
{
    /**
     * @param array<int, string> $contentIds
     */
    public function record(string $feature, string $status, array $contentIds = []): void
    {
        AIRequest::query()->create([
            'feature' => $feature,
            'actor_type' => 'system',
            'actor_id' => null,
            'status' => $status,
            'created_at' => now('UTC')->toImmutable(),
            'content_ids' => array_values(array_unique(array_slice($contentIds, 0, 20))),
        ]);
    }
}
