<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION enforce_appointment_status_transition()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                IF OLD.status = NEW.status THEN
                    RETURN NEW;
                END IF;

                IF NOT (
                    (OLD.status = 'pending' AND NEW.status IN ('confirmed', 'cancelled', 'expired'))
                    OR (OLD.status = 'confirmed' AND NEW.status IN ('completed', 'cancelled', 'no_show'))
                ) THEN
                    RAISE EXCEPTION 'Invalid appointment status transition'
                        USING ERRCODE = 'check_violation';
                END IF;

                RETURN NEW;
            END;
            $$;
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER appointments_status_transition
            BEFORE UPDATE OF status ON appointments
            FOR EACH ROW
            EXECUTE FUNCTION enforce_appointment_status_transition();
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS appointments_status_transition ON appointments');
        DB::statement('DROP FUNCTION IF EXISTS enforce_appointment_status_transition()');
    }
};
