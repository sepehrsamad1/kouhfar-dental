<?php

declare(strict_types=1);

namespace App\Services;

use App\AIUsage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class AIUsageRecorder
{
    public function record(
        string $feature,
        int $inputTokens,
        int $outputTokens,
        float $costEstimate,
        ?CarbonImmutable $now = null,
        string $provider = 'fake',
        string $model = 'fake-v1',
        string $capability = 'text',
        float $estimatedCostIrr = 0,
        ?float $actualCostIrr = null,
    ): void {
        $day = ($now ?? CarbonImmutable::now('UTC'))->toDateString();

        DB::table('ai_usage')->upsert(
            [[
                'id' => (string) str()->uuid(),
                'day' => $day,
                'feature' => $feature,
                'provider' => $provider,
                'model' => $model,
                'capability' => $capability,
                'input_tokens' => max(0, $inputTokens),
                'output_tokens' => max(0, $outputTokens),
                'cost_estimate' => max(0, $costEstimate),
                'estimated_cost_irr' => max(0, $estimatedCostIrr),
                'actual_cost_irr' => $actualCostIrr === null ? null : max(0, $actualCostIrr),
                'request_count' => 1,
            ]],
            ['day', 'feature', 'provider', 'model', 'capability'],
            [
                'input_tokens' => DB::raw('ai_usage.input_tokens + EXCLUDED.input_tokens'),
                'output_tokens' => DB::raw('ai_usage.output_tokens + EXCLUDED.output_tokens'),
                'cost_estimate' => DB::raw('ai_usage.cost_estimate + EXCLUDED.cost_estimate'),
                'estimated_cost_irr' => DB::raw('ai_usage.estimated_cost_irr + EXCLUDED.estimated_cost_irr'),
                'actual_cost_irr' => DB::raw('CASE WHEN EXCLUDED.actual_cost_irr IS NULL THEN ai_usage.actual_cost_irr ELSE COALESCE(ai_usage.actual_cost_irr, 0) + EXCLUDED.actual_cost_irr END'),
                'request_count' => DB::raw('ai_usage.request_count + 1'),
            ],
        );
    }

    public function model(): AIUsage
    {
        return new AIUsage;
    }
}
