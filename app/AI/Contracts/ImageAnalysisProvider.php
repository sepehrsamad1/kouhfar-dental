<?php

declare(strict_types=1);

namespace App\AI\Contracts;

interface ImageAnalysisProvider
{
    /**
     * @param  array<string, mixed>  $context
     * @return array{result: array<string, mixed>, input_tokens: int, output_tokens: int}
     */
    public function analyze(string $imageReference, array $context = []): array;
}
