<?php

declare(strict_types=1);

namespace Database\Factories;

use App\StaffUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

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

    public function configure(): static
    {
        return $this->afterMaking(function (StaffUser $staffUser): void {
            $staffUser->forceFill([
                'password_hash' => Hash::make('test-password'),
            ]);
        });
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['is_active' => true]);
    }
}
