<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PatientMatchFlagStatus;
use App\PatientMatchFlag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PatientMatchFlag>
 */
class PatientMatchFlagFactory extends Factory
{
    protected $model = PatientMatchFlag::class;

    public function definition(): array
    {
        $patientIds = [
            (string) Str::uuid(),
            (string) Str::uuid(),
        ];

        sort($patientIds, SORT_STRING);

        return [
            'patient_a_id' => $patientIds[0],
            'patient_b_id' => $patientIds[1],
            'reason' => 'synthetic-test-only',
            'status' => PatientMatchFlagStatus::Open,
        ];
    }
}
