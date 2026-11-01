<?php

use App\AIRequest;
use App\AIUsage;
use App\Services\AIProviderManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'ai.enabled' => true,
        'ai.capabilities.text.enabled' => true,
        'ai.capabilities.text.provider' => 'fake',
        'ai.capabilities.text.model' => 'fake-test',
        'ai.capabilities.image_analysis.enabled' => true,
        'ai.capabilities.image_analysis.provider' => 'fake',
        'ai.capabilities.image_transformation.enabled' => true,
        'ai.capabilities.image_transformation.provider' => 'fake',
        'ai.rate_limits.per_minute' => 10,
        'ai.rate_limits.daily_cost' => 0,
        'ai.pricing.input_per_1k' => 1.0,
        'ai.pricing.output_per_1k' => 2.0,
    ]);

    RateLimiter::clear('ai:'.substr(hash('sha256', 'task23'), 0, 24).':'.now('UTC')->format('Y-m-d-H-i'));
});

it('keeps AI disabled by default', function (): void {
    config(['ai.enabled' => false]);

    $result = app(AIProviderManager::class)->generateText('assistant', 'sensitive patient prompt');

    expect($result['status'])->toBe('disabled')
        ->and(AIRequest::query()->count())->toBe(0)
        ->and(AIUsage::query()->count())->toBe(0);
});

it('routes text through the deterministic fake provider and records usage without the prompt', function (): void {
    $result = app(AIProviderManager::class)->generateText(
        'assistant',
        'synthetic prompt containing patient-sensitive text',
        ['content_ids' => ['content-123'], 'ignored' => 'secret'],
    );

    expect($result['status'])->toBe('succeeded')
        ->and($result['output'])->toBe('Synthetic AI response.')
        ->and(AIRequest::query()->sole()->content_ids)->toBe(['content-123'])
        ->and(AIRequest::query()->sole()->toArray())->not->toContain('synthetic prompt containing patient-sensitive text')
        ->and(AIUsage::query()->sole()->request_count)->toBe(1)
        ->and((int) AIUsage::query()->sole()->input_tokens)->toBeGreaterThan(0)
        ->and((float) AIUsage::query()->sole()->cost_estimate)->toBeGreaterThan(0);
});

it('supports bounded image analysis and transformation simulations', function (): void {
    $analysis = app(AIProviderManager::class)->analyzeImage('smart-smile-simulation', 'opaque-image-ref', [
        'content_ids' => ['content-456'],
    ]);
    $transformation = app(AIProviderManager::class)->transformImage('smart-smile-simulation', 'opaque-image-ref');

    expect($analysis['status'])->toBe('succeeded')
        ->and($analysis['result']['simulation'])->toBeTrue()
        ->and($transformation['status'])->toBe('succeeded')
        ->and($transformation['image_reference'])->toBe('fake-transformed:opaque-image-ref')
        ->and(AIRequest::query()->count())->toBe(2);
});

it('falls back safely when an unapproved provider is selected', function (): void {
    Log::spy();
    config([
        'ai.capabilities.text.provider' => 'unapproved-provider',
        'ai.capabilities.text.fallback' => 'safe synthetic fallback',
    ]);

    $result = app(AIProviderManager::class)->generateText(
        'assistant',
        'secret prompt must never be logged',
        ['content_ids' => ['content-789']],
    );

    expect($result['status'])->toBe('fallback')
        ->and($result['output'])->toBe('safe synthetic fallback')
        ->and(AIRequest::query()->sole()->status)->toBe('failed')
        ->and(AIRequest::query()->sole()->content_ids)->toBe(['content-789']);

    Log::shouldHaveReceived('warning')->once()->with('AI provider failure', [
        'feature' => 'assistant',
        'capability' => 'text',
    ]);
});

it('blocks calls after the configured per-minute limit', function (): void {
    config(['ai.rate_limits.per_minute' => 1]);
    RateLimiter::clear('ai:'.substr(hash('sha256', 'limited-feature'), 0, 24).':'.now('UTC')->format('Y-m-d-H-i'));

    $manager = app(AIProviderManager::class);

    $first = $manager->generateText('limited-feature', 'one');
    $second = $manager->generateText('limited-feature', 'two');

    expect($first['status'])->toBe('succeeded')
        ->and($second['status'])->toBe('rate_limited')
        ->and(AIRequest::query()->count())->toBe(1);
});

it('enforces the configured daily cost ceiling before invoking a provider', function (): void {
    AIUsage::factory()->create([
        'day' => now('UTC')->toDateString(),
        'feature' => 'existing',
        'cost_estimate' => 5,
        'request_count' => 1,
    ]);

    config(['ai.rate_limits.daily_cost' => 5]);

    $result = app(AIProviderManager::class)->generateText('cost-limited', 'should not run');

    expect($result['status'])->toBe('rate_limited')
        ->and(AIRequest::query()->count())->toBe(0);
});
