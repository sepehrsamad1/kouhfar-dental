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
        Schema::create('staff_users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('email', 254)->unique();
            $table->string('display_name', 160);
            $table->string('password_hash');
            $table->string('role', 32);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('patients', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('full_name', 160);
            $table->string('mobile_normalized', 32);
            $table->date('date_of_birth')->nullable();
            $table->text('notes_sensitive')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();
            $table->index('mobile_normalized');
            $table->index('archived_at');
        });

        Schema::create('patient_match_flags', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_a_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUuid('patient_b_id')->constrained('patients')->restrictOnDelete();
            $table->string('reason', 120);
            $table->string('status', 24)->default('open');
            $table->timestampsTz();
            $table->unique(['patient_a_id', 'patient_b_id']);
            $table->index('status');
        });

        Schema::create('services', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name_fa', 160);
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedSmallInteger('buffer_before_minutes')->default(0);
            $table->unsignedSmallInteger('buffer_after_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public_bookable')->default(false);
            $table->timestampsTz();
            $table->index(['is_active', 'is_public_bookable']);
        });

        Schema::create('practitioners', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('display_name_fa', 160);
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('user_id')->nullable()->unique()->constrained('staff_users')->restrictOnDelete();
            $table->timestampsTz();
            $table->index('is_active');
        });

        Schema::create('practitioner_services', function (Blueprint $table): void {
            $table->foreignUuid('practitioner_id')->constrained('practitioners')->cascadeOnDelete();
            $table->foreignUuid('service_id')->constrained('services')->cascadeOnDelete();
            $table->primary(['practitioner_id', 'service_id']);
            $table->index('service_id');
        });

        Schema::create('resources', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name_fa', 160);
            $table->string('kind', 32);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->index(['kind', 'is_active']);
        });

        Schema::create('service_resources', function (Blueprint $table): void {
            $table->foreignUuid('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignUuid('resource_id')->constrained('resources')->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->primary(['service_id', 'resource_id']);
            $table->index('resource_id');
        });

        Schema::create('working_hours', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('practitioner_id')->nullable()->constrained('practitioners')->restrictOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('start_local');
            $table->time('end_local');
            $table->timestampsTz();
            $table->index(['practitioner_id', 'weekday']);
        });

        Schema::create('schedule_exceptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('practitioner_id')->nullable()->constrained('practitioners')->restrictOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('kind', 24);
            $table->string('reason', 255)->nullable();
            $table->timestampsTz();
            $table->index(['practitioner_id', 'starts_at', 'ends_at']);
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignUuid('service_id')->constrained('services')->restrictOnDelete();
            $table->string('status', 24);
            $table->timestampTz('start_at');
            $table->timestampTz('end_at');
            $table->string('source', 16);
            $table->timestampTz('expires_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('staff_users')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();
            $table->index(['status', 'start_at']);
            $table->index(['patient_id', 'start_at']);
            $table->index(['service_id', 'start_at']);
            $table->index('expires_at');
        });

        Schema::create('appointment_practitioners', function (Blueprint $table): void {
            $table->foreignUuid('appointment_id')->constrained('appointments')->restrictOnDelete();
            $table->foreignUuid('practitioner_id')->constrained('practitioners')->restrictOnDelete();
            $table->text('occupied');
            $table->boolean('active')->default(true);
            $table->primary(['appointment_id', 'practitioner_id']);
            $table->index('practitioner_id');
        });

        Schema::create('appointment_resources', function (Blueprint $table): void {
            $table->foreignUuid('appointment_id')->constrained('appointments')->restrictOnDelete();
            $table->foreignUuid('resource_id')->constrained('resources')->restrictOnDelete();
            $table->text('occupied');
            $table->boolean('active')->default(true);
            $table->primary(['appointment_id', 'resource_id']);
            $table->index('resource_id');
        });

        Schema::create('appointment_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('appointment_id')->constrained('appointments')->restrictOnDelete();
            $table->string('actor_type', 16);
            $table->uuid('actor_id')->nullable();
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->timestampTz('occurred_at');
            $table->string('reason', 255)->nullable();
            $table->timestampsTz();
            $table->index(['appointment_id', 'occurred_at']);
        });

        Schema::create('follow_up_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('appointment_id')->constrained('appointments')->restrictOnDelete();
            $table->string('token_hash', 128)->unique();
            $table->string('action_scope', 24);
            $table->timestampTz('expires_at');
            $table->timestampTz('used_at')->nullable();
            $table->timestampsTz();
            $table->unique(['appointment_id', 'action_scope']);
            $table->index('expires_at');
        });

        Schema::create('content_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('kind', 32);
            $table->string('locale', 12);
            $table->jsonb('body');
            $table->string('status', 24)->default('draft');
            $table->timestampTz('published_at')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'kind', 'locale']);
        });

        Schema::create('media_assets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('storage_key')->unique();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('byte_size');
            $table->char('sha256', 64);
            $table->foreignUuid('owner_patient_id')->nullable()->constrained('patients')->restrictOnDelete();
            $table->string('kind', 32);
            $table->foreignUuid('created_by')->nullable()->constrained('staff_users')->nullOnDelete();
            $table->timestampTz('deleted_at')->nullable();
            $table->timestampsTz();
            $table->index(['owner_patient_id', 'kind']);
            $table->index('deleted_at');
        });

        Schema::create('consent_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('patient_id')->constrained('patients')->restrictOnDelete();
            $table->string('scope', 48);
            $table->timestampTz('granted_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->string('document_ref', 255)->nullable();
            $table->timestampsTz();
            $table->index(['patient_id', 'scope', 'revoked_at']);
        });

        Schema::create('ai_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('feature', 32);
            $table->string('actor_type', 16);
            $table->uuid('actor_id')->nullable();
            $table->string('status', 24);
            $table->jsonb('content_ids')->default('[]');
            $table->timestampsTz();
            $table->index(['feature', 'status', 'created_at']);
        });

        Schema::create('ai_usages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->date('day');
            $table->string('feature', 32);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('cost_estimate', 12, 6)->default(0);
            $table->unsignedInteger('request_count')->default(0);
            $table->timestampsTz();
            $table->unique(['day', 'feature']);
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('staff_users')->restrictOnDelete();
            $table->string('token_hash', 128)->unique();
            $table->timestampTz('expires_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampTz('last_seen_at')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'expires_at']);
        });

        Schema::create('login_attempts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->char('identifier_hash', 64);
            $table->char('ip_hash', 64);
            $table->timestampTz('created_at');
            $table->boolean('success');
            $table->index(['identifier_hash', 'created_at']);
            $table->index(['ip_hash', 'created_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('actor_id')->nullable()->constrained('staff_users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('target_type', 80);
            $table->uuid('target_id')->nullable();
            $table->timestampTz('occurred_at');
            $table->uuid('request_id')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestampsTz();
            $table->index(['target_type', 'target_id', 'occurred_at']);
            $table->index(['actor_id', 'occurred_at']);
        });

        Schema::create('jobs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type', 80);
            $table->jsonb('payload')->default('{}');
            $table->string('status', 16)->default('queued');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('run_at');
            $table->timestampTz('locked_until')->nullable();
            $table->index(['status', 'run_at']);
        });

        Schema::create('notification_outbox', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('channel', 24);
            $table->char('recipient_hash', 64);
            $table->string('template', 80);
            $table->string('payload_ref', 255)->nullable();
            $table->string('status', 16)->default('queued');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('sent_at')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'created_at']);
        });

        Schema::create('idempotency_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('owner_scope', 160);
            $table->string('key', 200);
            $table->char('request_hash', 64);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->jsonb('response_body')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('expires_at');
            $table->unique(['owner_scope', 'key']);
            $table->index('expires_at');
        });

        Schema::create('deletion_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('target_type', 80);
            $table->uuid('target_id');
            $table->foreignUuid('requested_by')->constrained('staff_users')->restrictOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('staff_users')->restrictOnDelete();
            $table->string('status', 24)->default('requested');
            $table->timestampsTz();
            $table->index(['target_type', 'target_id']);
            $table->index(['status', 'created_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            $this->addPostgresIntegrity();
        }
    }

    private function addPostgresIntegrity(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        DB::statement('CREATE UNIQUE INDEX staff_users_email_lower_unique ON staff_users (LOWER(email))');
        DB::statement("ALTER TABLE staff_users ADD CONSTRAINT staff_users_role_check CHECK (role IN ('admin', 'reception', 'practitioner'))");
        DB::statement('ALTER TABLE patient_match_flags ADD CONSTRAINT patient_match_flags_order_check CHECK (patient_a_id < patient_b_id)');
        DB::statement("ALTER TABLE patient_match_flags ADD CONSTRAINT patient_match_flags_status_check CHECK (status IN ('open', 'dismissed', 'merged'))");
        DB::statement('ALTER TABLE services ADD CONSTRAINT services_duration_check CHECK (duration_minutes > 0)');
        DB::statement('ALTER TABLE services ADD CONSTRAINT services_buffers_check CHECK (buffer_before_minutes >= 0 AND buffer_after_minutes >= 0)');
        DB::statement("ALTER TABLE resources ADD CONSTRAINT resources_kind_check CHECK (kind IN ('chair', 'room'))");
        DB::statement('ALTER TABLE service_resources ADD CONSTRAINT service_resources_quantity_check CHECK (quantity > 0)');
        DB::statement('ALTER TABLE working_hours ADD CONSTRAINT working_hours_weekday_check CHECK (weekday BETWEEN 0 AND 6)');
        DB::statement('ALTER TABLE working_hours ADD CONSTRAINT working_hours_interval_check CHECK (end_local > start_local)');
        DB::statement("ALTER TABLE schedule_exceptions ADD CONSTRAINT schedule_exceptions_kind_check CHECK (kind IN ('holiday', 'leave', 'closed'))");
        DB::statement('ALTER TABLE schedule_exceptions ADD CONSTRAINT schedule_exceptions_interval_check CHECK (ends_at > starts_at)');
        DB::statement("ALTER TABLE appointments ADD CONSTRAINT appointments_status_check CHECK (status IN ('pending', 'confirmed', 'completed', 'cancelled', 'no_show', 'expired'))");
        DB::statement("ALTER TABLE appointments ADD CONSTRAINT appointments_source_check CHECK (source IN ('public', 'staff'))");
        DB::statement('ALTER TABLE appointments ADD CONSTRAINT appointments_interval_check CHECK (end_at > start_at)');
        DB::statement("ALTER TABLE appointments ADD CONSTRAINT appointments_pending_expiry_check CHECK (status <> 'pending' OR expires_at IS NOT NULL)");
        DB::statement('ALTER TABLE appointments ADD CONSTRAINT appointments_version_check CHECK (version > 0)');
        DB::statement('ALTER TABLE appointment_events ADD CONSTRAINT appointment_events_actor_check CHECK (actor_type IN (\'staff\', \'public\', \'system\'))');
        DB::statement("ALTER TABLE follow_up_tokens ADD CONSTRAINT follow_up_tokens_scope_check CHECK (action_scope IN ('view', 'cancel', 'reschedule'))");
        DB::statement("ALTER TABLE content_items ADD CONSTRAINT content_items_status_check CHECK (status IN ('draft', 'published', 'archived'))");
        DB::statement('ALTER TABLE ai_usages ADD CONSTRAINT ai_usages_nonnegative_check CHECK (input_tokens >= 0 AND output_tokens >= 0 AND cost_estimate >= 0 AND request_count >= 0)');
        DB::statement("ALTER TABLE jobs ADD CONSTRAINT jobs_status_check CHECK (status IN ('queued', 'running', 'done', 'failed', 'dead'))");
        DB::statement('ALTER TABLE jobs ADD CONSTRAINT jobs_attempts_check CHECK (attempts >= 0)');
        DB::statement('ALTER TABLE notification_outbox ADD CONSTRAINT notification_outbox_attempts_check CHECK (attempts >= 0)');
        DB::statement('ALTER TABLE idempotency_records ADD CONSTRAINT idempotency_records_response_status_check CHECK (response_status IS NULL OR response_status BETWEEN 100 AND 599)');

        DB::statement("ALTER TABLE appointment_practitioners ALTER COLUMN occupied TYPE tstzrange USING occupied::tstzrange");
        DB::statement("ALTER TABLE appointment_resources ALTER COLUMN occupied TYPE tstzrange USING occupied::tstzrange");
        DB::statement("ALTER TABLE appointment_practitioners ADD CONSTRAINT appointment_practitioners_range_check CHECK (NOT isempty(occupied) AND lower_inc(occupied) AND NOT upper_inc(occupied))");
        DB::statement("ALTER TABLE appointment_resources ADD CONSTRAINT appointment_resources_range_check CHECK (NOT isempty(occupied) AND lower_inc(occupied) AND NOT upper_inc(occupied))");
        DB::statement("ALTER TABLE appointment_practitioners ADD CONSTRAINT appointment_practitioners_no_overlap EXCLUDE USING gist (practitioner_id WITH =, occupied WITH &&) WHERE (active)");
        DB::statement("ALTER TABLE appointment_resources ADD CONSTRAINT appointment_resources_no_overlap EXCLUDE USING gist (resource_id WITH =, occupied WITH &&) WHERE (active)");
    }

    public function down(): void
    {
        Schema::dropIfExists('deletion_requests');
        Schema::dropIfExists('idempotency_records');
        Schema::dropIfExists('notification_outbox');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('login_attempts');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('ai_usages');
        Schema::dropIfExists('ai_requests');
        Schema::dropIfExists('consent_records');
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('content_items');
        Schema::dropIfExists('follow_up_tokens');
        Schema::dropIfExists('appointment_events');
        Schema::dropIfExists('appointment_resources');
        Schema::dropIfExists('appointment_practitioners');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('schedule_exceptions');
        Schema::dropIfExists('working_hours');
        Schema::dropIfExists('service_resources');
        Schema::dropIfExists('resources');
        Schema::dropIfExists('practitioner_services');
        Schema::dropIfExists('practitioners');
        Schema::dropIfExists('services');
        Schema::dropIfExists('patient_match_flags');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('staff_users');
    }
};
