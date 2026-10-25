<?php

declare(strict_types=1);

namespace Database\Factories;

use App\LoginAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoginAttempt>
 */
class LoginAttemptFactory extends Factory
{
    protected $model = LoginAttempt::class;

    public function definition(): array
    {
        return [
            'identifier_hash' => hash('sha256', fake()->safeEmail()),
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'created_at' => now(),
            'success' => false,
        ];
    }
}
