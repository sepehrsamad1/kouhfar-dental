<?php

declare(strict_types=1);

namespace App\AI;

use App\AI\Contracts\ImageAnalysisProvider;
use App\AI\Contracts\ImageTransformationProvider;
use App\AI\Contracts\TextGenerationProvider;

final class FakeAIProvider implements ImageAnalysisProvider, ImageTransformationProvider, TextGenerationProvider
{
    public function generate(string $prompt, array $context = []): array
    {
        return [
            'output' => 'Synthetic AI response.',
            'input_tokens' => $this->boundedTokenCount($prompt),
            'output_tokens' => 4,
        ];
    }

    public function analyze(string $imageReference, array $context = []): array
    {
        return [
            'result' => [
                'simulation' => true,
                'image_reference' => $imageReference,
            ],
            'input_tokens' => 1,
            'output_tokens' => 2,
        ];
    }

    public function transform(string $imageReference, array $context = []): array
    {
        return [
            'image_reference' => 'fake-transformed:'.$imageReference,
            'input_tokens' => 1,
            'output_tokens' => 1,
        ];
    }

    private function boundedTokenCount(string $prompt): int
    {
        return min(32, max(1, str_word_count($prompt)));
    }
}
