<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Practitioner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Practitioner>
 */
class PractitionerFactory extends Factory
{
    protected $model = Practitioner::class;

    public function definition(): array
    {
        return [
            'display_name_fa' => 'پزشک آزمایشی',
            'is_active' => false,
        ];
    }
}
