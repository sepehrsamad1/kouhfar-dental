<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Appointment;
use App\AppointmentEvent;
use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppointmentEvent>
 */
class AppointmentEventFactory extends Factory
{
    protected $model = AppointmentEvent::class;

    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'actor_type' => 'system',
            'actor_id' => null,
            'from_status' => AppointmentStatus::Pending,
            'to_status' => AppointmentStatus::Cancelled,
            'occurred_at' => now('UTC')->toImmutable(),
        ];
    }
}
