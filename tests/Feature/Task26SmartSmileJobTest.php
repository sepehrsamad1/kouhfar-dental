<?php

use AppAIFakeAIProvider;
use AppJobsProcessSmartSmile;
use AppServicesSmartSmileService;
use IlluminateFoundationTestingRefreshDatabase;
use IlluminateHttpUploadedFile;
use IlluminateSupportFacadesStorage;

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

    (new ProcessSmartSmile($session->id))->handle(app(AppServicesAIProviderManager::class));

    expect($session->refresh()->status)->toBe('failed');
});