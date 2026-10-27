<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "UPDATE appointment_practitioners ap
             SET occupied = tstzrange(
                 a.start_at - make_interval(mins => s.buffer_before_minutes),
                 a.end_at + make_interval(mins => s.buffer_after_minutes),
                 '[)'
             )
             FROM appointments a
             JOIN services s ON s.id = a.service_id
             WHERE a.id = ap.appointment_id"
        );

        DB::statement(
            "UPDATE appointment_resources ar
             SET occupied = tstzrange(
                 a.start_at - make_interval(mins => s.buffer_before_minutes),
                 a.end_at + make_interval(mins => s.buffer_after_minutes),
                 '[)'
             )
             FROM appointments a
             JOIN services s ON s.id = a.service_id
             WHERE a.id = ar.appointment_id"
        );

        DB::statement(
            "UPDATE appointment_practitioners ap
             SET active = (a.status IN ('pending', 'confirmed')
                 AND (a.status <> 'pending' OR a.expires_at IS NULL OR a.expires_at > CURRENT_TIMESTAMP))
             FROM appointments a
             WHERE a.id = ap.appointment_id"
        );

        DB::statement(
            "UPDATE appointment_resources ar
             SET active = (a.status IN ('pending', 'confirmed')
                 AND (a.status <> 'pending' OR a.expires_at IS NULL OR a.expires_at > CURRENT_TIMESTAMP))
             FROM appointments a
             WHERE a.id = ar.appointment_id"
        );

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION enforce_appointment_status_transition()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                IF NEW.status = OLD.status THEN
                    RETURN NEW;
                END IF;

                IF OLD.status = 'pending' AND NEW.status IN ('confirmed', 'cancelled', 'expired') THEN
                    RETURN NEW;
                END IF;

                IF OLD.status = 'confirmed' AND NEW.status IN ('completed', 'cancelled', 'no_show') THEN
                    RETURN NEW;
                END IF;

                RAISE EXCEPTION 'invalid appointment status transition from % to %',
                    OLD.status, NEW.status
                    USING ERRCODE = '23514';
            END;
            $$;
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER appointments_status_transition_trigger
            BEFORE UPDATE OF status ON appointments
            FOR EACH ROW
            EXECUTE FUNCTION enforce_appointment_status_transition();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS appointments_status_transition_trigger ON appointments');
        DB::unprepared('DROP FUNCTION IF EXISTS enforce_appointment_status_transition()');
    }
};
