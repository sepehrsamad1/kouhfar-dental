<?php

use App\AIRequest;
use App\AIUsage;
use App\Services\AIControlService;
use App\Services\AIProviderManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'ai.enabled' => true,
        'ai.capabilities.text.enabled' => true,
        'ai.capabilities.text.provider' => 'fake',
        'ai.capabilities.text.model' => 'fake-task27-model',
        'ai.rate_limits.per_minute' => 100,
        'ai.rate_limits.daily_cost' => 0,
        'ai.pricing_irr.text.input_per_1k' => 1000,
        'ai.pricing_irr.text.output_per_1k' => 2000,
        'ai.pricing_irr.models.fake-task27-model' => ['input_per_1k' => 1500, 'output_per_1k' => 2500],
    ]);
});

it('blocks provider calls at the approved monthly IRR budget ceiling', function (): void {
    $settings = app(AIControlService::class)->settings();
    $settings->update(['monthly_budget_irr' => 10_000_000]);

    AIUsage::factory()->create([
        'day' => now('UTC')->toDateString(),
        'estimated_cost_irr' => 10_000_000,
    ]);

    $result = app(AIProviderManager::class)->generateText('budget-test', 'must not reach provider');

    expect($result['status'])->toBe('rate_limited')
        ->and(AIRequest::query()->count())->toBe(1)
        ->and(AIRequest::query()->sole()->status)->toBe('blocked')
        ->and(app(AIControlService::class)->summary()['blocked'])->toBeTrue();
});

it('raises the approved 50, 80 and 100 percent financial thresholds from fake usage data', function (): void {
    $usage = AIUsage::factory()->create([
        'day' => now('UTC')->toDateString(),
        'estimated_cost_irr' => 5_000_000,
    ]);

    expect(app(AIControlService::class)->summary()['alerts'])->toBe([50]);

    $usage->update(['estimated_cost_irr' => 8_000_000]);
    expect(app(AIControlService::class)->summary()['alerts'])->toBe([50, 80]);

    $usage->update(['estimated_cost_irr' => 10_000_000]);
    expect(app(AIControlService::class)->summary()['alerts'])->toBe([50, 80, 100])
        ->and(app(AIControlService::class)->summary()['blocked'])->toBeTrue();
});

it('supports an administrator emergency disable without invoking the provider', function (): void {
    $settings = app(AIControlService::class)->settings();
    $settings->update(['emergency_disabled' => true]);

    $result = app(AIProviderManager::class)->generateText('emergency-disable-test', 'must not reach provider');

    expect($result['status'])->toBe('rate_limited')
        ->and(AIRequest::query()->count())->toBe(1)
        ->and(AIRequest::query()->sole()->status)->toBe('blocked')
        ->and(app(AIControlService::class)->summary()['emergency_disabled'])->toBeTrue();
});

it('records model-specific token counts and estimated IRR cost without claiming actual billing', function (): void {
    $result = app(AIProviderManager::class)->generateText('model-accounting-test', 'synthetic test prompt');

    $usage = AIUsage::query()->sole();
    $request = AIRequest::query()->sole();

    expect($result['status'])->toBe('succeeded')
        ->and($usage->provider)->toBe('fake')
        ->and($usage->model)->toBe('fake-task27-model')
        ->and($usage->capability)->toBe('text')
        ->and($usage->input_tokens + $usage->output_tokens)->toBeGreaterThan(0)
        ->and((float) $usage->estimated_cost_irr)->toBeGreaterThan(0)
        ->and($usage->actual_cost_irr)->toBeNull()
        ->and($request->model)->toBe('fake-task27-model')
        ->and($request->status)->toBe('succeeded')
        ->and($request->actual_cost_irr)->toBeNull();
});

it('reports provider quota as unknown when the fake provider exposes no billing API', function (): void {
    $summary = app(AIControlService::class)->summary();

    expect($summary['provider_quota_status'])->toBe('unknown')
        ->and($summary['provider_quota_alert_percentages'])->toBe([80, 95]);
});


it('serves an exact approved FAQ without provider usage when the monthly budget is exhausted', function (): void {
    $this->seed(DentalFaqTestingSeeder::class);
    $faq = ContentItem::query()
        ->where('content_key', 'dental-faq-KDF-001')
        ->where('locale', 'fa')
        ->firstOrFail();

    AIUsage::factory()->create([
        'day' => now('UTC')->toDateString(),
        'estimated_cost_irr' => 10_000_000,
    ]);

    $response = app(AssistantService::class)->respond($faq->bodyData()['title'], 'fa');

    expect($response['status'])->toBe('succeeded')
        ->and($response['answer'])->toBe($faq->bodyData()['text'])
        ->and($response['sources'][0]['content_key'])->toBe('dental-faq-KDF-001')
        ->and(AIRequest::query()->count())->toBe(0)
        ->and(AIUsage::query()->count())->toBe(1);
});

it('honours emergency disable before serving a direct FAQ answer', function (): void {
    $this->seed(DentalFaqTestingSeeder::class);
    $faq = ContentItem::query()
        ->where('content_key', 'dental-faq-KDF-001')
        ->where('locale', 'fa')
        ->firstOrFail();

    app(AIControlService::class)->settings()->update(['emergency_disabled' => true]);

    $response = app(AssistantService::class)->respond($faq->bodyData()['title'], 'fa');

    expect($response['status'])->toBe('disabled')
        ->and($response['answer'])->toContain('در حال به‌روزرسانی')
        ->and(AIRequest::query()->count())->toBe(0);
});
