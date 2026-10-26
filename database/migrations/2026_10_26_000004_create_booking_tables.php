<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('service_id');
            $table->string('status', 30);
            $table->timestampTz('start_at');
            $table->timestampTz('end_at');
            $table->string('source', 30);
            $table->timestampTz('expires_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();
            $table->foreign('patient_id')->references('id')->on('patients')->restrictOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('staff_users')->nullOnDelete();
            $table->index(['status', 'start_at']);
            $table->index(['patient_id', 'start_at']);
        });

        DB::statement('ALTER TABLE appointments ADD CONSTRAINT appointments_time_order CHECK (end_at > start_at)');
        DB::statement('ALTER TABLE appointments ADD CONSTRAINT appointments_status_check CHECK (status IN (\'pending\',\'confirmed\',\'completed\',\'cancelled\',\'no_show\',\'expired\'))');
        DB::statement('ALTER TABLE appointments ADD CONSTRAINT appointments_source_check CHECK (source IN (\'public\',\'staff\',\'system\'))');

        Schema::create('appointment_practitioners', function (Blueprint $table) {
            $table->uuid('appointment_id');
            $table->uuid('practitioner_id');
            $table->boolean('active')->default(true);
            $table->primary(['appointment_id', 'practitioner_id']);
            $table->foreign('appointment_id')->references('id')->on('appointments')->cascadeOnDelete();
            $table->foreign('practitioner_id')->references('id')->on('practitioners')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE appointment_practitioners ADD COLUMN occupied tstzrange');
        DB::statement('UPDATE appointment_practitioners ap SET occupied = tstzrange(a.start_at, a.end_at, \'[)\') FROM appointments a WHERE a.id = ap.appointment_id');
        DB::statement('ALTER TABLE appointment_practitioners ALTER COLUMN occupied SET NOT NULL');
        DB::statement('ALTER TABLE appointment_practitioners ADD CONSTRAINT appointment_practitioners_no_overlap EXCLUDE USING gist (practitioner_id WITH =, occupied WITH &&) WHERE (active)');

        Schema::create('appointment_resources', function (Blueprint $table) {
            $table->uuid('appointment_id');
            $table->uuid('resource_id');
            $table->boolean('active')->default(true);
            $table->primary(['appointment_id', 'resource_id']);
            $table->foreign('appointment_id')->references('id')->on('appointments')->cascadeOnDelete();
            $table->foreign('resource_id')->references('id')->on('resources')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE appointment_resources ADD COLUMN occupied tstzrange');
        DB::statement('UPDATE appointment_resources ar SET occupied = tstzrange(a.start_at, a.end_at, \'[)\') FROM appointments a WHERE a.id = ar.appointment_id');
        DB::statement('ALTER TABLE appointment_resources ALTER COLUMN occupied SET NOT NULL');
        DB::statement('ALTER TABLE appointment_resources ADD CONSTRAINT appointment_resources_no_overlap EXCLUDE USING gist (resource_id WITH =, occupied WITH &&) WHERE (active)');

        Schema::create('appointment_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('appointment_id');
            $table->string('actor_type', 40);
            $table->uuid('actor_id')->nullable();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->timestampTz('occurred_at');
            $table->foreign('appointment_id')->references('id')->on('appointments')->cascadeOnDelete();
            $table->index(['appointment_id', 'occurred_at']);
        });

        Schema::create('follow_up_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('appointment_id');
            $table->string('token_hash', 128)->unique();
            $table->string('action_scope', 80);
            $table->timestampTz('expires_at');
            $table->timestampTz('used_at')->nullable();
            $table->foreign('appointment_id')->references('id')->on('appointments')->cascadeOnDelete();
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_up_tokens');
        Schema::dropIfExists('appointment_events');
        Schema::dropIfExists('appointment_resources');
        Schema::dropIfExists('appointment_practitioners');
        Schema::dropIfExists('appointments');
    }
};
