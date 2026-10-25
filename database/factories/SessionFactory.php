<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Session;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Session>
 */
class SessionFactory extends Factory
{
    protected $model = Session::class;

    public function definition(): array
    {
        return [
            'user_id' => fake()->uuid(),
            'token_hash' => hash('sha256', fake()->uuid()),
            'created_at' => now(),
            'expires_at' => now()->addDays(7),
            'revoked_at' => null,
            'last_seen_at' => null,
        ];
    }
}
