<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Appointment;
use App\ClinicWorkingHour;
use App\Patient;
use App\Practitioner;
use App\Resource;
use App\Service;
use App\StaffUser;
use App\WorkingHour;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use LogicException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new LogicException('DatabaseSeeder is disabled in production.');
        }

        $adminId = '11111111-1111-4111-8111-111111111111';
        $receptionId = '22222222-2222-4222-8222-222222222222';
        $practitionerUserId = '33333333-3333-4333-8333-333333333333';
        $patientId = '44444444-4444-4444-8444-444444444444';
        $serviceId = '55555555-5555-4555-8555-555555555555';
        $practitionerId = '66666666-6666-4666-8666-666666666666';
        $resourceId = '77777777-7777-4777-8777-777777777777';
        $appointmentId = '88888888-8888-4888-8888-888888888888';

        $this->upsertStaff($adminId, 'seed-admin@example.invalid', 'Synthetic Admin', 'admin');
        $this->upsertStaff($receptionId, 'seed-reception@example.invalid', 'Synthetic Reception', 'reception');
        $this->upsertStaff($practitionerUserId, 'seed-practitioner@example.invalid', 'Synthetic Practitioner User', 'practitioner');

        Patient::query()->updateOrCreate(['id' => $patientId], [
            'full_name' => 'Synthetic Test Patient',
            'mobile_normalized' => '09000000001',
            'archived_at' => null,
        ]);

        Service::query()->updateOrCreate(['id' => $serviceId], [
            'name_fa' => 'خدمت آزمایشی',
            'duration_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
            'is_active' => true,
            'is_public_bookable' => false,
        ]);

        Practitioner::query()->updateOrCreate(['id' => $practitionerId], [
            'display_name_fa' => 'پزشک آزمایشی',
            'is_active' => true,
        ]);

        Resource::query()->updateOrCreate(['id' => $resourceId], [
            'name_fa' => 'یونیت آزمایشی',
            'kind' => 'dental_unit',
            'is_active' => true,
        ]);

        DB::table('practitioner_services')->updateOrInsert(
            ['practitioner_id' => $practitionerId, 'service_id' => $serviceId],
            []
        );

        DB::table('service_resources')->updateOrInsert(
            ['service_id' => $serviceId, 'resource_id' => $resourceId],
            ['quantity' => 1]
        );

        WorkingHour::query()->updateOrCreate(
            ['practitioner_id' => $practitionerId, 'weekday' => 1, 'start_local' => '09:00:00', 'end_local' => '17:00:00'],
            []
        );

        foreach ([1, 2, 3, 4, 6, 7] as $weekday) {
            ClinicWorkingHour::query()->firstOrCreate(
                ['weekday' => $weekday],
                [
                    'start_local' => '12:00',
                    'end_local' => '20:00',
                    'timezone' => 'Asia/Tehran',
                ]
            );
        }

        $start = now('UTC')->addDays(7)->startOfHour();
        $appointment = Appointment::query()->updateOrCreate(['id' => $appointmentId], [
            'patient_id' => $patientId,
            'service_id' => $serviceId,
            'status' => 'confirmed',
            'start_at' => $start,
            'end_at' => $start->copy()->addMinutes(30),
            'source' => 'staff',
            'expires_at' => null,
            'created_by' => $adminId,
            'version' => 1,
        ]);

        DB::table('appointment_practitioners')->updateOrInsert(
            ['appointment_id' => $appointment->id, 'practitioner_id' => $practitionerId],
            ['active' => true, 'occupied' => DB::raw("tstzrange('".$start->toIso8601String()."', '".$start->copy()->addMinutes(30)->toIso8601String()."', '[)')")]
        );

        DB::table('appointment_resources')->updateOrInsert(
            ['appointment_id' => $appointment->id, 'resource_id' => $resourceId],
            ['active' => true, 'occupied' => DB::raw("tstzrange('".$start->toIso8601String()."', '".$start->copy()->addMinutes(30)->toIso8601String()."', '[)')")]
        );
    }

    private function upsertStaff(string $id, string $email, string $displayName, string $role): void
    {
        $staff = StaffUser::query()->firstOrNew(['id' => $id]);
        $staff->forceFill([
            'email' => $email,
            'display_name' => $displayName,
            'password_hash' => Hash::make(Str::random(32)),
            'role' => $role,
            'is_active' => true,
        ])->save();
    }
}
