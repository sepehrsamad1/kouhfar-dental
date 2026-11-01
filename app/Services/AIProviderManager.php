<?php

declare(strict_types=1);

namespace App\Services;

use App\AI\AIProviderException;
use App\AI\Contracts\ImageAnalysisProvider;
use App\AI\Contracts\ImageTransformationProvider;
use App\AI\Contracts\TextGenerationProvider;
use App\AI\FakeAIProvider;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

final class AIProviderManager
{
    public function __construct(
        private readonly AIUsageRecorder $usage,
    ) {
    }

    /** @return array{status: string, output: ?string, input_tokens: int, output_tokens: int} */
    public function generateText(string $feature, string $prompt, array $context = []): array
    {
        if (! $this->enabled('text')) {
            return $this->disabled('text');
        }

        if (! $this->allowed($feature)) {
            return $this->limited('text');
        }

        try {
            $provider = $this->textProvider();
            $result = $provider->generate($prompt, $this->boundedContext($context));
            $this->record($feature, $result);

            return ['status' => 'succeeded', 'output' => $result['output'], 'input_tokens' => $result['input_tokens'], 'output_tokens' => $result['output_tokens']];
        } catch (Throwable $e) {
            report($e);

            return $this->fallback($feature, 'text');
        }
    }

    /** @return array{status: string, result: array<string, mixed>|null, input_tokens: int, output_tokens: int} */
    public function analyzeImage(string $feature, string $imageReference, array $context = []): array
    {
        if (! $this->enabled('image_analysis')) {
            return ['status' => 'disabled', 'result' => null, 'input_tokens' => 0, 'output_tokens' => 0];
        }

        if (! $this->allowed($feature)) {
            return ['status' => 'rate_limited', 'result' => null, 'input_tokens' => 0, 'output_tokens' => 0];
        }

        try {
            $result = $this->imageAnalysisProvider()->analyze($imageReference, $this->boundedContext($context));
            $this->record($feature, $result);

            return ['status' => 'succeeded', 'result' => $result['result'], 'input_tokens' => $result['input_tokens'], 'output_tokens' => $result['output_tokens']];
        } catch (Throwable $e) {
            report($e);

            return ['status' => 'fallback', 'result' => null, 'input_tokens' => 0, 'output_tokens' => 0];
        }
    }

    /** @return array{status: string, image_reference: ?string, input_tokens: int, output_tokens: int} */
    public function transformImage(string $feature, string $imageReference, array $context = []): array
    {
        if (! $this->enabled('image_transformation')) {
            return ['status' => 'disabled', 'image_reference' => null, 'input_tokens' => 0, 'output_tokens' => 0];
        }

        if (! $this->allowed($feature)) {
            return ['status' => 'rate_limited', 'image_reference' => null, 'input_tokens' => 0, 'output_tokens' => 0];
        }

        try {
            $result = $this->imageTransformationProvider()->transform($imageReference, $this->boundedContext($context));
            $this->record($feature, $result);

            return ['status' => 'succeeded', 'image_reference' => $result['image_reference'], 'input_tokens' => $result['input_tokens'], 'output_tokens' => $result['output_tokens']];
        } catch (Throwable $e) {
            report($e);

            return ['status' => 'fallback', 'image_reference' => null, 'input_tokens' => 0, 'output_tokens' => 0];
        }
    }

    private function enabled(string $capability): bool
    {
        return (bool) config('ai.enabled', false) && (bool) config("ai.capabilities.{$capability}.enabled", false);
    }

    private function allowed(string $feature): bool
    {
        $key = 'ai:'.$feature.':'.now('UTC')->format('Y-m-d-H-i');
        $limit = max(1, (int) config('ai.rate_limits.per_minute', 10));

        return ! RateLimiter::tooManyAttempts($key, $limit)
            && tap(true, fn () => RateLimiter::hit($key, 60));
    }

    private function textProvider(): TextGenerationProvider
    {
        return $this->provider(config('ai.capabilities.text.provider', 'fake'), TextGenerationProvider::class);
    }

    private function imageAnalysisProvider(): ImageAnalysisProvider
    {
        return $this->provider(config('ai.capabilities.image_analysis.provider', 'fake'), ImageAnalysisProvider::class);
    }

    private function imageTransformationProvider(): ImageTransformationProvider
    {
        return $this->provider(config('ai.capabilities.image_transformation.provider', 'fake'), ImageTransformationProvider::class);
    }

    private function provider(string $name, string $contract): object
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

    /** @param array{input_tokens:int,output_tokens:int} $result */
    private function record(string $feature, array $result): void
    {
        $input = max(0, (int) $result['input_tokens']);
        $output = max(0, (int) $result['output_tokens']);
        $cost = (($input * (float) config('ai.pricing.input_per_1k', 0.0)) + ($output * (float) config('ai.pricing.output_per_1k', 0.0))) / 1000;

        $this->usage->record($feature, $input, $output, $cost);
    }

    /** @return array{status:string,output:?string,input_tokens:int,output_tokens:int} */
    private function fallback(string $feature, string $capability): array
    {
        $fallback = config("ai.capabilities.{$capability}.fallback", null);

        return [
            'status' => 'fallback',
            'output' => is_string($fallback) ? $fallback : null,
            'input_tokens' => 0,
            'output_tokens' => 0,
        ];
    }

    /** @return array{status:string,output:?string,input_tokens:int,output_tokens:int} */
    private function disabled(string $capability): array
    {
        return ['status' => 'disabled', 'output' => null, 'input_tokens' => 0, 'output_tokens' => 0];
    }

    /** @return array{status:string,output:?string,input_tokens:int,output_tokens:int} */
    private function limited(string $capability): array
    {
        return ['status' => 'rate_limited', 'output' => null, 'input_tokens' => 0, 'output_tokens' => 0];
    }
}
