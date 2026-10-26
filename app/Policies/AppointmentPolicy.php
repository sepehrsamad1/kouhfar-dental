<?php

declare(strict_types=1);

namespace App\Policies;

use App\Appointment;
use App\StaffUser;

class AppointmentPolicy
{
    public function view(StaffUser $user, Appointment $appointment): bool
    {
        return $user->role === 'admin'
            || $user->role === 'reception'
            || ($user->role === 'practitioner' && $this->ownsAppointment($user, $appointment));
    }

    public function create(StaffUser $user): bool
    {
        return in_array($user->role, ['admin', 'reception'], true);
    }

    public function update(StaffUser $user, Appointment $appointment): bool
    {
        return in_array($user->role, ['admin', 'reception'], true);
    }

    public function cancel(StaffUser $user, Appointment $appointment): bool
    {
        return in_array($user->role, ['admin', 'reception'], true);
    }

    public function complete(StaffUser $user, Appointment $appointment): bool
    {
        return $user->role === 'admin'
            || $user->role === 'reception'
            || ($user->role === 'practitioner' && $this->ownsAppointment($user, $appointment));
    }

    private function ownsAppointment(StaffUser $user, Appointment $appointment): bool
    {
        return $appointment->practitioners()
            ->whereHas('staffUser', fn ($query) => $query->whereKey($user->id))
            ->exists();
    }
}
