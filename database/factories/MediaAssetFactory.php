<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MediaAssetKind;
use App\MediaAsset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MediaAsset>
 */
class MediaAssetFactory extends Factory
{
    protected $model = MediaAsset::class;

    public function definition(): array
    {
        return [
            'storage_key' => (string) Str::uuid(),
            'mime_type' => 'application/octet-stream',
            'byte_size' => 0,
            'sha256' => str_repeat('0', 64),
            'owner_patient_id' => null,
            'kind' => MediaAssetKind::Other,
            'created_by' => null,
            'created_at' => now('UTC')->toImmutable(),
            'deleted_at' => null,
        ];
    }
}
