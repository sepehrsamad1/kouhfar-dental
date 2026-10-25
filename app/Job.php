<?php

declare(strict_types=1);

namespace App;

use App\Enums\JobStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'jobs';

    public $timestamps = false;

    protected $fillable = [
        'type',
        'payload',
        'status',
        'attempts',
        'run_at',
        'locked_until',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => JobStatus::class,
            'attempts' => 'integer',
            'run_at' => 'immutable_datetime',
            'locked_until' => 'immutable_datetime',
        ];
    }
}
