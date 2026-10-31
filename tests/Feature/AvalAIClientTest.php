<?php

declare(strict_types=1);

use App\Services\AvalAIClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;

beforeEach(function (): void {
    config([
        'services.avalai.api_key' => 'test-avalai-key',
        'services.avalai.base_url' => 'https://api.avalai.ir/v1',
        'services.avalai.model' => 'test-model',
        'services.avalai.timeout' => 12,
    ]);
});

it('sends an authenticated chat completion request to AvalAI', function (): void {
    Http::fake([
        'api.avalai.ir/v1/chat/completions' => Http::response([
            'id' => 'chatcmpl-test',
            'choices' => [['message' => ['role' => 'assistant', 'content' => 'سلام']]],
        ]),
    ]);

    $result = app(AvalAIClient::class)->chat([
        ['role' => 'user', 'content' => 'سلام'],
    ], ['temperature' => 0.2]);

    expect($result['id'])->toBe('chatcmpl-test');

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://api.avalai.ir/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer test-avalai-key')
            && $request['model'] === 'test-model'
            && $request['messages'][0]['content'] === 'سلام'
            && $request['temperature'] === 0.2;
    });
});

it('fails before making a request when the API key is missing', function (): void {
    config(['services.avalai.api_key' => null]);
    Http::preventStrayRequests();

    expect(fn () => app(AvalAIClient::class)->chat([
        ['role' => 'user', 'content' => 'test'],
    ]))->toThrow(RuntimeException::class, 'AvalAI API key is not configured.');
});

it('fails before making a request when no model is configured', function (): void {
    config(['services.avalai.model' => null]);
    Http::preventStrayRequests();

    expect(fn () => app(AvalAIClient::class)->chat([
        ['role' => 'user', 'content' => 'test'],
    ]))->toThrow(RuntimeException::class, 'AvalAI model is not configured.');
});
