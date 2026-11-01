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
    ): void {
        $day = ($now ?? CarbonImmutable::now('UTC'))->toDateString();

        DB::table('ai_usage')->upsert(
            [[
                'id' => (string) str()->uuid(),
                'day' => $day,
                'feature' => $feature,
                'input_tokens' => max(0, $inputTokens),
                'output_tokens' => max(0, $outputTokens),
                'cost_estimate' => max(0, $costEstimate),
                'request_count' => 1,
            ]],
            ['day', 'feature'],
            [
                'input_tokens' => DB::raw('ai_usage.input_tokens + EXCLUDED.input_tokens'),
                'output_tokens' => DB::raw('ai_usage.output_tokens + EXCLUDED.output_tokens'),
                'cost_estimate' => DB::raw('ai_usage.cost_estimate + EXCLUDED.cost_estimate'),
                'request_count' => DB::raw('ai_usage.request_count + 1'),
            ],
        );
    }

    public function model(): AIUsage
    {
        return new AIUsage;
    }
}
