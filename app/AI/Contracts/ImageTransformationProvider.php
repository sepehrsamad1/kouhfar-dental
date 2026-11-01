<?php

declare(strict_types=1);

namespace App\AI\Contracts;

interface ImageTransformationProvider
{
    /**
     * @param  array<string, mixed>  $context
     * @return array{image_reference: string, input_tokens: int, output_tokens: int}
     */
    public function transform(string $imageReference, array $context = []): array;
}
