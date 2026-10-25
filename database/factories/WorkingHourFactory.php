<?php

declare(strict_types=1);

namespace Database\Factories;

use App\WorkingHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkingHour>
 */
class WorkingHourFactory extends Factory
{
    protected $model = WorkingHour::class;

    public function definition(): array
    {
        return [
            'practitioner_id' => null,
            'weekday' => 1,
            'start_local' => '09:00',
            'end_local' => '10:00',
        ];
    }
}
