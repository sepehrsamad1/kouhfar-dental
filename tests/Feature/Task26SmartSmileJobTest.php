<?php

use App\Jobs\ProcessSmartSmile;
use App\Services\AIProviderManager;
use App\Services\SmartSmileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('fails safely when the private image disappears before processing', function (): void {
    config([
        'smart_smile.enabled' => true,
        'smart_smile.release_gate' => true,
        'ai.enabled' => true,
        'ai.capabilities.image_analysis.enabled' => true,
        'ai.capabilities.image_analysis.provider' => 'fake',
    ]);

    Storage::fake('media');

    $granted = app(SmartSmileService::class)->grantConsent();
    $session = app(SmartSmileService::class)->upload(
        $granted['session'],
        UploadedFile::fake()->image('synthetic.png', 100, 100),
    );

    Storage::disk('media')->delete($session->mediaAsset->storage_key);

    (new ProcessSmartSmile($session->id))->handle(app(AIProviderManager::class));

    expect($session->refresh()->status)->toBe('failed');
});
