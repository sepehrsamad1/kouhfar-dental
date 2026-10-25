<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Appointment;
use App\Enums\AppointmentStatus;
use App\Patient;
use App\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $startAt = now('UTC')->toImmutable()->addYear()->startOfDay();

        return [
            'patient_id' => Patient::factory(),
            'service_id' => Service::factory(),
            'status' => AppointmentStatus::Cancelled,
            'start_at' => $startAt,
            'end_at' => $startAt->addHour(),
            'source' => 'staff',
            'expires_at' => null,
            'created_by' => null,
            'version' => 1,
        ];
    }
}
