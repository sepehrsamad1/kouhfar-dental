<?php

declare(strict_types=1);

namespace Database\Factories;

use App\SmartSmileSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SmartSmileSession> */
class SmartSmileSessionFactory extends Factory
{
    protected $model = SmartSmileSession::class;

    public function definition(): array
    {
        return [
            'consent_token_hash' => hash('sha256', fake()->unique()->uuid()),
            'consent_version' => 'v1',
            'consented_at' => now('UTC'),
            'revoked_at' => null,
            'media_asset_id' => null,
            'status' => 'consented',
            'result' => null,
            'result_generated_at' => null,
        ];
    }
}
