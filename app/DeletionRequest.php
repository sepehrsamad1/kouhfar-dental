<?php

declare(strict_types=1);

namespace App;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $reviewed_at
 * @property CarbonImmutable|null $resolved_at
 */
class DeletionRequest extends Model
{
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'target_type',
        'target_id',
        'requested_by',
        'approved_by',
        'reviewed_by',
        'reviewed_at',
        'resolved_at',
        'resolution_code',
        'status',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'reviewed_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
        ];
    }
}
