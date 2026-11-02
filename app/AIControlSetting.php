<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class AIControlSetting extends Model
{
    use HasUuids;

    protected $table = 'ai_control_settings';

    protected $fillable = ['monthly_budget_irr', 'emergency_disabled', 'provider_token_limits', 'model_budget_limits_irr'];

    protected function casts(): array
    {
        return [
            'monthly_budget_irr' => 'integer',
            'emergency_disabled' => 'boolean',
            'provider_token_limits' => 'array',
            'model_budget_limits_irr' => 'array',
        ];
    }
}
