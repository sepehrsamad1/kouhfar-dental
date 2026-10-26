<?php

declare(strict_types=1);

namespace App\Policies;

use App\Patient;
use App\StaffUser;

class PatientPolicy
{
    public function view(StaffUser $user, Patient $patient): bool
    {
        return $this->isAdmin($user)
            || $user->role === 'reception'
            || ($user->role === 'practitioner' && $this->ownsPatient($user, $patient));
    }

    public function update(StaffUser $user, Patient $patient): bool
    {
        return $this->isAdmin($user)
            || $user->role === 'reception'
            || ($user->role === 'practitioner' && $this->ownsPatient($user, $patient));
    }

    public function archive(StaffUser $user, Patient $patient): bool
    {
        return $this->isAdmin($user);
    }

    private function isAdmin(StaffUser $user): bool
    {
        return $user->role === 'admin';
    }

    private function ownsPatient(StaffUser $user, Patient $patient): bool
    {
        return $patient->appointments()
            ->whereHas('practitioners', fn ($query) => $query->whereKey($user->id))
            ->exists();
    }
}
