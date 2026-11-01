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
        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (! Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table): void {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestampTz('failed_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('notification_outbox')) {
            Schema::create('notification_outbox', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('appointment_id')->index();
                $table->string('type', 80);
                $table->string('dedupe_key', 191)->unique();
                $table->timestampTz('scheduled_at')->index();
                $table->timestampTz('sent_at')->nullable();
                $table->unsignedInteger('attempts')->default(0);
                $table->text('last_error')->nullable();
                $table->foreign('appointment_id')->references('id')->on('appointments')->cascadeOnDelete();
            });

            return;
        }

        // Task 7 already created this table with a legacy notification shape.
        // Keep that schema/data intact while extending it for Task 22.
        Schema::table('notification_outbox', function (Blueprint $table): void {
            if (! Schema::hasColumn('notification_outbox', 'appointment_id')) {
                $table->uuid('appointment_id')->nullable()->index();
            }

            if (! Schema::hasColumn('notification_outbox', 'type')) {
                $table->string('type', 80)->nullable();
            }

            if (! Schema::hasColumn('notification_outbox', 'dedupe_key')) {
                $table->string('dedupe_key', 191)->nullable()->unique();
            }

            if (! Schema::hasColumn('notification_outbox', 'scheduled_at')) {
                $table->timestampTz('scheduled_at')->nullable()->index();
            }

            if (! Schema::hasColumn('notification_outbox', 'last_error')) {
                $table->text('last_error')->nullable();
            }
        });

        // Legacy columns remain for backwards compatibility, but Task 22
        // records do not populate them. They therefore must accept NULL.
        foreach (['channel', 'recipient_hash', 'template', 'payload_ref', 'status', 'created_at'] as $column) {
            if (Schema::hasColumn('notification_outbox', $column)) {
                DB::statement(sprintf(
                    'ALTER TABLE notification_outbox ALTER COLUMN %s DROP NOT NULL',
                    $column,
                ));
            }
        }

        if (Schema::hasColumn('notification_outbox', 'appointment_id')) {
            DB::statement(
                'ALTER TABLE notification_outbox ADD CONSTRAINT notification_outbox_appointment_id_foreign FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE',
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_outbox');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
    }
};
