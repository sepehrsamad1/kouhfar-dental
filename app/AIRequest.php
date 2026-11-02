<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIRequest extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'ai_requests';

    public $timestamps = false;

    protected $fillable = [
        'feature',
        'actor_type',
        'actor_id',
        'status',
        'created_at',
        'provider',
        'model',
        'capability',
        'input_tokens',
        'output_tokens',
        'estimated_cost_irr',
        'actual_cost_irr',
        'content_ids',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'estimated_cost_irr' => 'decimal:2',
            'actual_cost_irr' => 'decimal:2',
            'content_ids' => 'array',
        ];
    }
}
