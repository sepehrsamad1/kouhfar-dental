<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CompletedJobHistory extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'completed_job_history';

    protected $fillable = [
        'job_class',
        'queue_name',
        'attempts',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
