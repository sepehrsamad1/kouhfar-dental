<?php

use AppContentItem;
use AppEnumsContentItemStatus;
use AppEnumsContentKind;
use IlluminateFoundationTestingRefreshDatabase;
use IlluminateSupportFacadesRateLimiter;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'ai.enabled' => true,
        'ai.capabilities.text.enabled' => true,
        'ai.capabilities.text.provider' => 'fake',
        'ai.rate_limits.per_minute' => 100,
        'ai.rate_limits.daily_cost' => 0,
    ]);
});

function task25ApiKnowledge(): void
{
    ContentItem::query()->create([
        'content_key' => 'patient-rights',
        'kind' => ContentKind::ClinicInfo,
        'locale' => 'fa',
        'version' => 1,
        'body' => [
            'title' => 'حقوق بیمار',
            'text' => 'حریم خصوصی و دریافت اطلاعات کافی از حقوق بیمار است.',
        ],
        'status' => ContentItemStatus::Published,
        'approval_scope' => 'general',
        'published_at' => now('UTC'),
    ]);
}

it('serves the bounded assistant through the versioned API and returns safe source links', function (): void {
    task25ApiKnowledge();

    $response = $this->postJson('/api/v1/assistant', [
        'message' => 'حریم خصوصی بیمار چه حقی است؟',
        'locale' => 'fa',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('status', 'succeeded')
        ->assertJsonPath('sources.0.content_key', 'patient-rights')
        ->assertJsonPath('sources.0.version', 1)
        ->assertJsonPath('sources.0.url', fn (mixed $url): bool => is_string($url) && str_contains($url, '/content/patient-rights/fa'));
});

it('rejects malformed assistant input server-side', function (): void {
    $this->postJson('/api/v1/assistant', [
        'message' => str_repeat('x', 2001),
        'locale' => 'de',
    ])->assertUnprocessable();
});

it('returns an explicit disabled state without exposing provider details', function (): void {
    task25ApiKnowledge();
    config([
        'ai.enabled' => false,
        'ai.capabilities.text.enabled' => false,
    ]);

    $response = $this->postJson('/api/v1/assistant', [
        'message' => 'حریم خصوصی بیمار چیست؟',
        'locale' => 'fa',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('status', 'disabled')
        ->assertJsonMissingPath('error');
});

it('returns 429 after the public endpoint throttle is exhausted', function (): void {
    task25ApiKnowledge();
    RateLimiter::clear('127.0.0.1');

    $statuses = [];
    for ($i = 0; $i < 21; $i++) {
        $statuses[] = $this->postJson('/api/v1/assistant', [
            'message' => 'حریم خصوصی بیمار چیست؟',
            'locale' => 'fa',
        ])->status();
    }

    expect($statuses)->toContain(429);
});
