<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name', 200);
            $table->string('mobile_normalized', 32)->unique();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name_fa', 200);
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedSmallInteger('buffer_before_minutes')->default(0);
            $table->unsignedSmallInteger('buffer_after_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public_bookable')->default(false);
            $table->timestampsTz();
        });

        Schema::create('practitioners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('display_name_fa', 200);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('practitioner_services', function (Blueprint $table) {
            $table->uuid('practitioner_id');
            $table->uuid('service_id');
            $table->primary(['practitioner_id', 'service_id']);
            $table->foreign('practitioner_id')->references('id')->on('practitioners')->cascadeOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
        });

        Schema::create('resources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name_fa', 200);
            $table->string('kind', 80);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('service_resources', function (Blueprint $table) {
            $table->uuid('service_id');
            $table->uuid('resource_id');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->primary(['service_id', 'resource_id']);
            $table->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
            $table->foreign('resource_id')->references('id')->on('resources')->cascadeOnDelete();
        });

        Schema::create('working_hours', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('practitioner_id');
            $table->unsignedSmallInteger('weekday');
            $table->time('start_local');
            $table->time('end_local');
            $table->timestampsTz();
            $table->foreign('practitioner_id')->references('id')->on('practitioners')->cascadeOnDelete();
            $table->index(['practitioner_id', 'weekday']);
        });

        Schema::create('schedule_exceptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('practitioner_id');
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('kind', 40);
            $table->timestampsTz();
            $table->foreign('practitioner_id')->references('id')->on('practitioners')->cascadeOnDelete();
            $table->index(['practitioner_id', 'starts_at']);
        });

        Schema::create('patient_match_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_a_id');
            $table->uuid('patient_b_id');
            $table->text('reason');
            $table->string('status', 30)->default('open');
            $table->timestampsTz();
            $table->foreign('patient_a_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('patient_b_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->unique(['patient_a_id', 'patient_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_match_flags');
        Schema::dropIfExists('schedule_exceptions');
        Schema::dropIfExists('working_hours');
        Schema::dropIfExists('service_resources');
        Schema::dropIfExists('resources');
        Schema::dropIfExists('practitioner_services');
        Schema::dropIfExists('practitioners');
        Schema::dropIfExists('services');
        Schema::dropIfExists('patients');
    }
};
