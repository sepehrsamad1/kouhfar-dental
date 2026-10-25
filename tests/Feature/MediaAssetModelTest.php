<?php

use App\Enums\MediaAssetKind;
use App\MediaAsset;
use App\Patient;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

it('allows the planned media metadata fields', function () {
    expect((new MediaAsset)->getFillable())->toBe([
        'storage_key',
        'mime_type',
        'byte_size',
        'sha256',
        'owner_patient_id',
        'kind',
        'created_by',
    ]);
});

it('casts media size kind and timestamps', function () {
    expect((new MediaAsset)->getCasts())
        ->toMatchArray([
            'byte_size' => 'integer',
            'kind' => MediaAssetKind::class,
            'created_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ]);
});

it('defines the optional owning patient relationship', function () {
    $relationship = (new MediaAsset)->ownerPatient();

    expect($relationship)
        ->toBeInstanceOf(BelongsTo::class)
        ->and($relationship->getForeignKeyName())->toBe('owner_patient_id')
        ->and($relationship->getRelated())->toBeInstanceOf(Patient::class);
});

it('makes synthetic media metadata without saving it', function () {
    $mediaAsset = MediaAsset::factory()->make();

    expect($mediaAsset->exists)->toBeFalse()
        ->and($mediaAsset->mime_type)->toBe('application/octet-stream')
        ->and($mediaAsset->byte_size)->toBe(0)
        ->and($mediaAsset->kind)->toBe(MediaAssetKind::Other)
        ->and($mediaAsset->owner_patient_id)->toBeNull();
});
