<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'mobile_normalized' => fake()->unique()->numerify('09#########'),
        ];
    }
}
