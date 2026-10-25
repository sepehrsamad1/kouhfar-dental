<?php

declare(strict_types=1);

namespace Database\Factories;

use App\FollowUpToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FollowUpToken>
 */
class FollowUpTokenFactory extends Factory
{
    protected $model = FollowUpToken::class;

    public function definition(): array
    {
        return [
            'appointment_id' => fake()->uuid(),
            'token_hash' => hash('sha256', fake()->uuid()),
            'action_scope' => fake()->randomElement(['view', 'cancel', 'reschedule']),
            'expires_at' => now()->addHours(2),
            'used_at' => null,
        ];
    }
}
