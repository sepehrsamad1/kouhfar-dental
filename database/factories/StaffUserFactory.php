<?php

declare(strict_types=1);

namespace Database\Factories;

use App\StaffUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffUser>
 */
class StaffUserFactory extends Factory
{
    protected $model = StaffUser::class;

    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'display_name' => fake()->name(),
            'role' => 'synthetic-test-only',
            'is_active' => false,
        ];
    }
}
