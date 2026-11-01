<?php

declare(strict_types=1);

namespace App;

use IlluminateDatabaseEloquentConcernsHasUuids;
use IlluminateDatabaseEloquentFactoriesHasFactory;
use IlluminateDatabaseEloquentModel;
use IlluminateDatabaseEloquentRelationsBelongsTo;

class SmartSmileSession extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'consent_token_hash',
        'consent_version',
        'consented_at',
        'revoked_at',
        'media_asset_id',
        'status',
        'result',
    ];

    protected function casts(): array
    {
        return [
            'consented_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'result' => 'array',
        ];
    }

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
    }
}