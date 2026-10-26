<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use LogicException;

class DatabaseSeeder extends Seeder
{
    private const STAFF_ID = '00000000-0000-4000-8000-000000000001';
    private const PATIENT_ID = '00000000-0000-4000-8000-000000000002';
    private const SERVICE_ID = '00000000-0000-4000-8000-000000000003';
    private const PRACTITIONER_ID = '00000000-0000-4000-8000-000000000004';
    private const RESOURCE_ID = '00000000-0000-4000-8000-000000000005';
    private const HOURS_ID = '00000000-0000-4000-8000-000000000006';
    private const APPOINTMENT_ID = '00000000-0000-4000-8000-000000000007';
    private const EVENT_ID = '00000000-0000-4000-8000-000000000008';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new LogicException('Synthetic development data seeding is disabled in production.');
        }

        $now = now('UTC');
        $syntheticAppointmentStart = '2024-01-01 09:00:00+00';
        $syntheticAppointmentEnd = '2024-01-01 09:30:00+00';

        DB::table('staff_users')->insertOrIgnore([
            'id' => self::STAFF_ID,
            'email' => 'task08-admin@example.invalid',
            'display_name' => 'Synthetic inactive admin fixture',
            'password_hash' => Hash::make(Str::random(64)),
            'role' => 'admin',
            'is_active' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('patients')->insertOrIgnore([
            'id' => self::PATIENT_ID,
            'full_name' => 'Synthetic test patient, not a real person',
            'mobile_normalized' => '00000000000',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('services')->insertOrIgnore([
            'id' => self::SERVICE_ID,
            'name_fa' => 'خدمت آزمایشی، داده ساختگی',
            'duration_minutes' => 30,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
            'is_active' => false,
            'is_public_bookable' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('practitioners')->insertOrIgnore([
            'id' => self::PRACTITIONER_ID,
            'display_name_fa' => 'پزشک آزمایشی، غیرفعال',
            'is_active' => false,
            'user_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('resources')->insertOrIgnore([
            'id' => self::RESOURCE_ID,
            'name_fa' => 'منبع آزمایشی، غیرفعال',
            'kind' => 'chair',
            'is_active' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('practitioner_services')->insertOrIgnore([
            'practitioner_id' => self::PRACTITIONER_ID,
            'service_id' => self::SERVICE_ID,
        ]);
        DB::table('service_resources')->insertOrIgnore([
            'service_id' => self::SERVICE_ID,
            'resource_id' => self::RESOURCE_ID,
            'quantity' => 1,
        ]);
        DB::table('working_hours')->insertOrIgnore([
            'id' => self::HOURS_ID,
            'practitioner_id' => self::PRACTITIONER_ID,
            'weekday' => 1,
            'start_local' => '09:00:00',
            'end_local' => '09:30:00',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('appointments')->insertOrIgnore([
            'id' => self::APPOINTMENT_ID,
            'patient_id' => self::PATIENT_ID,
            'service_id' => self::SERVICE_ID,
            'status' => 'completed',
            'start_at' => $syntheticAppointmentStart,
            'end_at' => $syntheticAppointmentEnd,
            'source' => 'staff',
            'expires_at' => null,
            'created_by' => self::STAFF_ID,
            'version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $occupied = '["2024-01-01 09:00:00+00","2024-01-01 09:30:00+00")';
        DB::table('appointment_practitioners')->insertOrIgnore([
            'appointment_id' => self::APPOINTMENT_ID,
            'practitioner_id' => self::PRACTITIONER_ID,
            'occupied' => $occupied,
            'active' => false,
        ]);
        DB::table('appointment_resources')->insertOrIgnore([
            'appointment_id' => self::APPOINTMENT_ID,
            'resource_id' => self::RESOURCE_ID,
            'occupied' => $occupied,
            'active' => false,
        ]);
        DB::table('appointment_events')->insertOrIgnore([
            'id' => self::EVENT_ID,
            'appointment_id' => self::APPOINTMENT_ID,
            'actor_type' => 'system',
            'actor_id' => null,
            'from_status' => null,
            'to_status' => 'completed',
            'occurred_at' => $syntheticAppointmentEnd,
            'reason' => 'Synthetic fixture only; not clinic history.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
