<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class AssistantEvaluationRunner
{
    public function __construct(
        private readonly AssistantService $assistant,
        private readonly AIReleaseGate $gate,
        private readonly AIControlService $controls,
    ) {}

    /** @return array<string, mixed> */
    public function run(): array
    {
        if (app()->environment('production') || config('ai.capabilities.text.provider', 'fake') !== 'fake') {
            throw new RuntimeException('The deterministic Task 27 evaluation runner is restricted to non-production fake-provider runs.');
        }

        config(['ai.rate_limits.per_minute' => max(1000, (int) config('ai.rate_limits.per_minute', 10))]);

        $path = (string) config('ai_evaluation.dataset_path');
        if (! is_file($path)) {
            throw new RuntimeException('The configured AI evaluation dataset is missing.');
        }

        $dataset = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($dataset) || ! is_array($dataset['cases'] ?? null)) {
            throw new RuntimeException('The configured AI evaluation dataset is invalid.');
        }

        $responses = [];
        foreach ($dataset['cases'] as $case) {
            $responses[$case['id']] = $this->assistant->respond($case['question'], $case['locale']);
        }

        $report = $this->gate->evaluate($dataset, $responses);
        $report['run'] = [
            'id' => (string) Str::uuid(),
            'completed_at' => now('UTC')->toIso8601String(),
            'provider' => (string) config('ai.capabilities.text.provider', 'fake'),
            'model' => (string) config('ai.capabilities.text.model', 'fake-v1'),
            'prompt_version' => (string) config('ai_evaluation.prompt_version', 'task27-persona-v1'),
            'dataset_version' => (string) $dataset['version'],
            'environment' => app()->environment(),
        ];
        $report['budget'] = $this->controls->summary();
        $report['evaluation_mode'] = 'deterministic_provider_contract';
        $report['semantic_model_accuracy_claim'] = false;

        $relativePath = 'ai-evaluations/'.now('UTC')->format('Y/m').'/'.$report['run']['id'].'.json';
        Storage::disk('local')->put($relativePath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $report['report_path'] = $relativePath;

        return $report;
    }
}
