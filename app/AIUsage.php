<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIUsage extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'ai_usage';

    public $timestamps = false;

    protected $fillable = [
        'day',
        'feature',
        'input_tokens',
        'output_tokens',
        'cost_estimate',
        'request_count',
    ];

    protected function casts(): array
    {
        return [
            'day' => 'immutable_date',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'request_count' => 'integer',
        ];
    }
}
