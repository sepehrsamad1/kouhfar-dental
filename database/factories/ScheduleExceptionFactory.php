<?php

declare(strict_types=1);

namespace Database\Factories;

use App\ScheduleException;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleException>
 */
class ScheduleExceptionFactory extends Factory
{
    protected $model = ScheduleException::class;

    public function definition(): array
    {
        $startsAt = now('UTC')->toImmutable()->addYear()->startOfDay();

        return [
            'practitioner_id' => null,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addHour(),
            'kind' => 'closed',
        ];
    }
}
