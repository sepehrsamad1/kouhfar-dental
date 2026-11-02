<?php

declare(strict_types=1);

namespace App\Services;

use App\AI\AIProviderException;
use App\AI\Contracts\ImageAnalysisProvider;
use App\AI\Contracts\ImageTransformationProvider;
use App\AI\Contracts\TextGenerationProvider;
use App\AI\FakeAIProvider;
use App\AIUsage;
use App\StaffUser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

final class AIProviderManager
{
    private readonly AIUsageRecorder $usage;

    private readonly AIRequestRecorder $requests;

    private readonly AIControlService $controls;

    public function __construct(AIUsageRecorder $usage, AIRequestRecorder $requests, AIControlService $controls)
    {
        $this->usage = $usage;
        $this->requests = $requests;
        $this->controls = $controls;
    }

    /** @return array{status: string, output: ?string, input_tokens: int, output_tokens: int} */
    public function generateText(string $feature, string $prompt, array $context = []): array
    {
        if ($this->enabled('text') === false) {
            $this->recordDenied($feature, 'text', 'disabled');

            return $this->disabled();
        }

        if ($this->allowed($feature, 'text') === false) {
            $this->recordBlocked($feature, 'text');

            return $this->limited();
        }

        try {
            $result = $this->textProvider()->generate($prompt, $this->boundedContext($context));
            $meta = $this->record($feature, $result, 'text');
            $this->requests->record($feature, 'succeeded', $this->contentIds($context), $meta['provider'], $meta['model'], 'text', $result['input_tokens'], $result['output_tokens'], $meta['estimated_cost_irr']);

            return [
                'status' => 'succeeded',
                'output' => $result['output'],
                'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
            ];
        } catch (Throwable) {
            $this->safeFailureLog($feature, 'text');
            $this->requests->record($feature, 'failed', $this->contentIds($context), $this->configuredProvider('text'), $this->configuredModel('text'), 'text');

            return $this->fallback('text');
        }
    }

    /** @return array{status: string, result: array<string, mixed>|null, input_tokens: int, output_tokens: int} */
    public function analyzeImage(string $feature, string $imageReference, array $context = []): array
    {
        if ($this->enabled('image_analysis') === false) {
            $this->recordDenied($feature, 'image_analysis', 'disabled');

            return ['status' => 'disabled', 'result' => null, 'input_tokens' => 0, 'output_tokens' => 0];
        }

        if ($this->allowed($feature, 'image_analysis') === false) {
            $this->recordBlocked($feature, 'image_analysis');

            return ['status' => 'rate_limited', 'result' => null, 'input_tokens' => 0, 'output_tokens' => 0];
        }

        try {
            $result = $this->imageAnalysisProvider()->analyze($imageReference, $this->boundedContext($context));
            $meta = $this->record($feature, $result, 'image_analysis');
            $this->requests->record($feature, 'succeeded', $this->contentIds($context), $meta['provider'], $meta['model'], 'image_analysis', $result['input_tokens'], $result['output_tokens'], $meta['estimated_cost_irr']);

            return [
                'status' => 'succeeded',
                'result' => $result['result'],
                'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
            ];
        } catch (Throwable) {
            $this->safeFailureLog($feature, 'image_analysis');
            $this->requests->record($feature, 'failed', $this->contentIds($context), $this->configuredProvider('image_analysis'), $this->configuredModel('image_analysis'), 'image_analysis');

            return ['status' => 'fallback', 'result' => null, 'input_tokens' => 0, 'output_tokens' => 0];
        }
    }

    /** @return array{status: string, image_reference: ?string, input_tokens: int, output_tokens: int} */
    public function transformImage(string $feature, string $imageReference, array $context = []): array
    {
        if ($this->enabled('image_transformation') === false) {
            $this->recordDenied($feature, 'image_transformation', 'disabled');

            return ['status' => 'disabled', 'image_reference' => null, 'input_tokens' => 0, 'output_tokens' => 0];
        }

        if ($this->allowed($feature, 'image_transformation') === false) {
            $this->recordBlocked($feature, 'image_transformation');

            return ['status' => 'rate_limited', 'image_reference' => null, 'input_tokens' => 0, 'output_tokens' => 0];
        }

        try {
            $result = $this->imageTransformationProvider()->transform($imageReference, $this->boundedContext($context));
            $meta = $this->record($feature, $result, 'image_transformation');
            $this->requests->record($feature, 'succeeded', $this->contentIds($context), $meta['provider'], $meta['model'], 'image_transformation', $result['input_tokens'], $result['output_tokens'], $meta['estimated_cost_irr']);

            return [
                'status' => 'succeeded',
                'image_reference' => $result['image_reference'],
                'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
            ];
        } catch (Throwable) {
            $this->safeFailureLog($feature, 'image_transformation');
            $this->requests->record($feature, 'failed', $this->contentIds($context), $this->configuredProvider('image_transformation'), $this->configuredModel('image_transformation'), 'image_transformation');

            return ['status' => 'fallback', 'image_reference' => null, 'input_tokens' => 0, 'output_tokens' => 0];
        }
    }

    private function recordBlocked(string $feature, string $capability): void
    {
        $this->recordDenied($feature, $capability, 'blocked');
    }

    private function recordDenied(string $feature, string $capability, string $status): void
    {
        $this->requests->record(
            $feature,
            $status,
            [],
            $this->configuredProvider($capability),
            $this->configuredModel($capability),
            $capability,
        );
    }

    private function enabled(string $capability): bool
    {
        return (bool) config('ai.enabled', false) && (bool) config("ai.capabilities.{$capability}.enabled", false);
    }

    private function allowed(string $feature, string $capability): bool
    {
        if ($this->controls->allowsRequests($this->configuredModel($capability)) === false) {
            return false;
        }

        if ($this->configuredProvider($capability) !== 'fake'
            && ($this->hasApiKeyConfigured($capability) === false || $this->hasPricingConfigured($capability) === false)) {
            return false;
        }

        $dailyLimit = (float) config('ai.rate_limits.daily_cost', 0);
        if ($dailyLimit > 0) {
            $spent = (float) (AIUsage::query()
                ->whereDate('day', now('UTC')->toDateString())
                ->sum('cost_estimate'));

            if ($spent >= $dailyLimit) {
                return false;
            }
        }

        $request = request();
        $authenticatedUser = $request->user('staff');
        $identity = $authenticatedUser instanceof StaffUser ? $authenticatedUser->getAuthIdentifier() : null;

        if ($identity === null) {
            $identity = $request->hasSession() ? $request->session()->getId() : $request->ip();
        }

        $key = 'ai:'.substr(hash('sha256', $feature.'|'.(string) ($identity ?? 'cli')), 0, 24).':'.now('UTC')->format('Y-m-d-H-i');
        $limit = max(1, (int) config('ai.rate_limits.per_minute', 10));

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return false;
        }

        RateLimiter::hit($key, 60);

        return true;
    }

    private function textProvider(): TextGenerationProvider
    {
        return $this->provider(config('ai.capabilities.text.provider', 'fake'));
    }

    private function imageAnalysisProvider(): ImageAnalysisProvider
    {
        return $this->provider(config('ai.capabilities.image_analysis.provider', 'fake'));
    }

    private function imageTransformationProvider(): ImageTransformationProvider
    {
        return $this->provider(config('ai.capabilities.image_transformation.provider', 'fake'));
    }

    private function provider(string $name): FakeAIProvider
    {
        if ($name === 'fake') {
            return app(FakeAIProvider::class);
        }

        throw new AIProviderException('AI provider is not approved.');
    }

    /** @param array<string, mixed> $context */
    private function boundedContext(array $context): array
    {
        return array_intersect_key($context, array_flip((array) config('ai.allowed_context_keys', [])));
    }

    /** @param array{input_tokens:int,output_tokens:int} $result @return array{provider:string,model:string,estimated_cost_irr:float} */
    private function record(string $feature, array $result, string $capability): array
    {
        $input = max(0, (int) $result['input_tokens']);
        $output = max(0, (int) $result['output_tokens']);
        $cost = (($input * (float) config('ai.pricing.input_per_1k', 0.0)) + ($output * (float) config('ai.pricing.output_per_1k', 0.0))) / 1000;
        $provider = $this->configuredProvider($capability);
        $model = $this->configuredModel($capability);
        $modelPricing = (array) config('ai.pricing_irr.models', []);
        $rates = (array) ($modelPricing[$model] ?? config("ai.pricing_irr.{$capability}", []));
        $inputRate = (float) ($rates['input_per_1k'] ?? 0);
        $outputRate = (float) ($rates['output_per_1k'] ?? 0);
        $estimatedCostIrr = (($input * $inputRate) + ($output * $outputRate)) / 1000;

        $this->usage->record($feature, $input, $output, $cost, null, $provider, $model, $capability, $estimatedCostIrr);

        return ['provider' => $provider, 'model' => $model, 'estimated_cost_irr' => $estimatedCostIrr];
    }

    private function hasApiKeyConfigured(string $capability): bool
    {
        $apiKey = config("ai.capabilities.{$capability}.api_key");

        return is_string($apiKey) && trim($apiKey) !== '';
    }

    private function hasPricingConfigured(string $capability): bool
    {
        $modelPricing = (array) config('ai.pricing_irr.models', []);
        $rates = (array) ($modelPricing[$this->configuredModel($capability)] ?? config("ai.pricing_irr.{$capability}", []));

        return (float) ($rates['input_per_1k'] ?? 0) + (float) ($rates['output_per_1k'] ?? 0) > 0;
    }

    private function configuredProvider(string $capability): string
    {
        return (string) config("ai.capabilities.{$capability}.provider", 'fake');
    }

    private function configuredModel(string $capability): string
    {
        return (string) config("ai.capabilities.{$capability}.model", 'fake-v1');
    }

    /** @param array<string, mixed> $context @return array<int, string> */
    private function contentIds(array $context): array
    {
        $ids = $context['content_ids'] ?? [];

        if (is_array($ids) === false) {
            return [];
        }

        return array_values(array_filter($ids, static fn (mixed $id): bool => is_string($id) && strlen($id) <= 100));
    }

    private function safeFailureLog(string $feature, string $capability): void
    {
        Log::warning('AI provider failure', [
            'feature' => $feature,
            'capability' => $capability,
        ]);
    }

    /** @return array{status:string,output:?string,input_tokens:int,output_tokens:int} */
    private function fallback(string $capability): array
    {
        $fallback = config("ai.capabilities.{$capability}.fallback");

        return [
            'status' => 'fallback',
            'output' => is_string($fallback) ? $fallback : null,
            'input_tokens' => 0,
            'output_tokens' => 0,
        ];
    }

    /** @return array{status:string,output:?string,input_tokens:int,output_tokens:int} */
    private function disabled(): array
    {
        return ['status' => 'disabled', 'output' => null, 'input_tokens' => 0, 'output_tokens' => 0];
    }

    /** @return array{status:string,output:?string,input_tokens:int,output_tokens:int} */
    private function limited(): array
    {
        return ['status' => 'rate_limited', 'output' => null, 'input_tokens' => 0, 'output_tokens' => 0];
    }
}
