<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['services', 'practitioners', 'resources', 'working_hours', 'schedule_exceptions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedInteger('version')->default(1);
            });
        }

        DB::statement('ALTER TABLE working_hours ADD CONSTRAINT working_hours_weekday_check CHECK (weekday BETWEEN 1 AND 7 AND weekday <> 5)');
        DB::statement('ALTER TABLE working_hours ADD CONSTRAINT working_hours_time_order CHECK (start_local < end_local)');
        DB::statement('ALTER TABLE schedule_exceptions ADD CONSTRAINT schedule_exceptions_time_order CHECK (ends_at > starts_at)');
        DB::statement('ALTER TABLE services ADD CONSTRAINT services_duration_positive_check CHECK (duration_minutes > 0)');

        Schema::table('schedule_exceptions', function (Blueprint $table): void {
            $table->uuid('practitioner_id')->nullable()->change();
        });

        Schema::create('clinic_working_hours', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedSmallInteger('weekday');
            $table->time('start_local');
            $table->time('end_local');
            $table->string('timezone', 64)->default('Asia/Tehran');
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();
            $table->unique('weekday');
        });

        DB::statement('ALTER TABLE clinic_working_hours ADD CONSTRAINT clinic_working_hours_weekday_check CHECK (weekday BETWEEN 1 AND 7 AND weekday <> 5)');
        DB::statement('ALTER TABLE clinic_working_hours ADD CONSTRAINT clinic_working_hours_time_order CHECK (start_local < end_local)');

        Schema::create('schedule_breaks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('practitioner_id')->nullable();
            $table->unsignedSmallInteger('weekday');
            $table->time('start_local');
            $table->time('end_local');
            $table->string('timezone', 64)->default('Asia/Tehran');
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();
            $table->foreign('practitioner_id')->references('id')->on('practitioners')->restrictOnDelete();
            $table->index(['practitioner_id', 'weekday']);
        });

        DB::statement('ALTER TABLE schedule_breaks ADD CONSTRAINT schedule_breaks_weekday_check CHECK (weekday BETWEEN 1 AND 7 AND weekday <> 5)');
        DB::statement('ALTER TABLE schedule_breaks ADD CONSTRAINT schedule_breaks_time_order CHECK (start_local < end_local)');
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_breaks');
        Schema::dropIfExists('clinic_working_hours');

        if (DB::table('schedule_exceptions')->whereNull('practitioner_id')->exists()) {
            throw new RuntimeException('Cannot roll back Task 12 while clinic-wide schedule exceptions exist; resolve them explicitly first.');
        }

        DB::statement('ALTER TABLE services DROP CONSTRAINT IF EXISTS services_duration_positive_check');
        DB::statement('ALTER TABLE schedule_exceptions DROP CONSTRAINT IF EXISTS schedule_exceptions_time_order');
        DB::statement('ALTER TABLE working_hours DROP CONSTRAINT IF EXISTS working_hours_time_order');
        DB::statement('ALTER TABLE working_hours DROP CONSTRAINT IF EXISTS working_hours_weekday_check');

        Schema::table('schedule_exceptions', function (Blueprint $table): void {
            $table->uuid('practitioner_id')->nullable(false)->change();
            $table->dropColumn('version');
        });

        foreach (['services', 'practitioners', 'resources', 'working_hours'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('version');
            });
        }
    }
};
