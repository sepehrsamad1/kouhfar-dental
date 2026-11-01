<?php

use App\Jobs\ProcessSmartSmile;
use App\MediaAsset;
use App\Services\AIProviderManager;
use App\Services\SmartSmileService;
use App\SmartSmileSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'smart_smile.enabled' => true,
        'smart_smile.release_gate' => true,
        'ai.enabled' => true,
        'ai.capabilities.image_analysis.enabled' => true,
        'ai.capabilities.image_analysis.provider' => 'fake',
        'ai.rate_limits.per_minute' => 50,
        'ai.rate_limits.daily_cost' => 0,
    ]);

    Storage::fake('media');
});

it('fails closed when the release gate is disabled', function (): void {
    config(['smart_smile.release_gate' => false]);

    $this->postJson('/api/v1/smart-smile/consent')
        ->assertStatus(503)
        ->assertJsonPath('status', 'disabled');
});

it('requires explicit consent before upload', function (): void {
    $file = UploadedFile::fake()->image('synthetic.png', 100, 100);

    $this->postJson('/api/v1/smart-smile/upload', [
        'consent_token' => str_repeat('x', 64),
        'media' => $file,
    ])->assertForbidden();

    expect(MediaAsset::query()->count())->toBe(0);
});

it('creates consent and queues a private image processing job', function (): void {
    Bus::fake();

    $consent = $this->postJson('/api/v1/smart-smile/consent')
        ->assertCreated()
        ->assertJsonPath('non_diagnostic', true);

    $token = $consent->json('consent_token');
    $file = UploadedFile::fake()->image('synthetic.png', 100, 100);

    $response = $this->post('/api/v1/smart-smile/upload', [
        'consent_token' => $token,
        'media' => $file,
    ]);

    $response->assertAccepted()->assertJsonPath('status', 'queued');

    $asset = MediaAsset::query()->firstOrFail();

    expect($asset->owner_patient_id)->toBeNull()
        ->and($asset->kind->value)->toBe('smile_input')
        ->and(Storage::disk('media')->exists($asset->storage_key))->toBeTrue();

    Bus::assertDispatched(ProcessSmartSmile::class);
});

it('rejects invalid image content and does not trust the filename', function (): void {
    $consent = $this->postJson('/api/v1/smart-smile/consent');
    $token = $consent->json('consent_token');
    $file = UploadedFile::fake()->createWithContent('fake.jpg', 'not an image');

    $this->withHeader('Accept', 'application/json')->post('/api/v1/smart-smile/upload', [
        'consent_token' => $token,
        'media' => $file,
    ])->assertStatus(422);

    expect(MediaAsset::query()->count())->toBe(0);
});

it('processes through the fake provider and never exposes clinical findings', function (): void {
    $service = app(SmartSmileService::class);
    $granted = $service->grantConsent();
    $session = $service->upload(
        $granted['session'],
        UploadedFile::fake()->image('synthetic.png', 100, 100),
    );

    (new ProcessSmartSmile($session->id))->handle(app(AIProviderManager::class));

    $session->refresh();

    expect($session->status)->toBe('completed')
        ->and($session->result['message'])->toContain('غیرتشخیصی')
        ->and($session->result['message'])->not->toContain('بیماری');
});

it('allows deletion through the consent token and soft-deletes the private image', function (): void {
    $granted = app(SmartSmileService::class)->grantConsent();
    $session = app(SmartSmileService::class)->upload(
        $granted['session'],
        UploadedFile::fake()->image('synthetic.png', 100, 100),
    );

    $this->deleteJson('/api/v1/smart-smile', ['consent_token' => $granted['token']])
        ->assertOk()
        ->assertJsonPath('status', 'deleted');

    expect(SmartSmileSession::query()->find($session->id)->status)->toBe('deleted')
        ->and(MediaAsset::withTrashed()->find($session->media_asset_id)->deleted_at)->not->toBeNull();
});

it('rejects revoked consent and cannot access session data without the token', function (): void {
    $granted = app(SmartSmileService::class)->grantConsent();
    app(SmartSmileService::class)->delete($granted['session']);

    $this->postJson('/api/v1/smart-smile/status', ['consent_token' => 'wrong-token'])
        ->assertNotFound();
});
