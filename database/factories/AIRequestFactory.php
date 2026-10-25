<?php

declare(strict_types=1);

namespace Database\Factories;

use App\AIRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AIRequest>
 */
class AIRequestFactory extends Factory
{
    protected $model = AIRequest::class;

    public function definition(): array
    {
        return [
            'feature' => 'assistant',
            'actor_type' => 'synthetic',
            'actor_id' => null,
            'status' => 'synthetic-test-only',
            'created_at' => now('UTC')->toImmutable(),
            'content_ids' => [],
        ];
    }
}
