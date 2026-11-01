<?php

declare(strict_types=1);

namespace App\AI\Contracts;

interface TextGenerationProvider
{
    /**
     * @param  array<string, mixed> $context
     * @return array{output: string, input_tokens: int, output_tokens: int}
     */
    public function generate(string $prompt, array $context = []): array;
}
