<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

final class AvalAIClient
{
    /**
     * Send a chat-completions request to AvalAI.
     *
     * Messages and options must contain only data approved for external processing.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function chat(array $messages, array $options = []): array
    {
        $apiKey = config('services.avalai.api_key');
        $baseUrl = config('services.avalai.base_url');
        $model = $options['model'] ?? config('services.avalai.model');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new \RuntimeException('AvalAI API key is not configured.');
        }

        if (! is_string($baseUrl) || trim($baseUrl) === '') {
            throw new \RuntimeException('AvalAI base URL is not configured.');
        }

        if (! is_string($model) || trim($model) === '') {
            throw new \RuntimeException('AvalAI model is not configured.');
        }

        unset($options['model'], $options['messages']);

        $response = Http::baseUrl(rtrim($baseUrl, '/'))
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(max(1, (int) config('services.avalai.timeout', 30)))
            ->post('/chat/completions', [
                ...$options,
                'model' => $model,
                'messages' => $messages,
            ]);

        $data = $response->throw()->json();

        if (! is_array($data)) {
            throw new \UnexpectedValueException('AvalAI returned an invalid JSON response.');
        }

        return $data;
    }
}
