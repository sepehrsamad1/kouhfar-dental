<?php

declare(strict_types=1);

namespace App;

use App\Enums\MediaAssetKind;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaAsset extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'storage_key',
        'mime_type',
        'byte_size',
        'sha256',
        'owner_patient_id',
        'kind',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'byte_size' => 'integer',
            'kind' => MediaAssetKind::class,
            'created_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }

    public function ownerPatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'owner_patient_id');
    }
}
