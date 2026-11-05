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
        $consentedAt = now('UTC');

        return [
            'consent_token_hash' => hash('sha256', fake()->unique()->uuid()),
            'consent_version' => 'v1',
            'consented_at' => $consentedAt,
            'revoked_at' => null,
            'token_expires_at' => $consentedAt->copy()->addHours(24),
            'media_asset_id' => null,
            'status' => 'consented',
            'result' => null,
            'result_generated_at' => null,
            'result_expires_at' => null,
        ];
    }
}
