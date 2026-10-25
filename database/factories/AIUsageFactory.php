<?php

declare(strict_types=1);

namespace Database\Factories;

use App\AIUsage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AIUsage>
 */
class AIUsageFactory extends Factory
{
    protected $model = AIUsage::class;

    public function definition(): array
    {
        return [
            'day' => now('UTC')->toImmutable()->toDateString(),
            'feature' => 'synthetic-test-only',
            'input_tokens' => 0,
            'output_tokens' => 0,
            'cost_estimate' => 0,
            'request_count' => 0,
        ];
    }
}
