<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 120);
            $table->jsonb('payload');
            $table->string('status', 30);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('run_at');
            $table->timestampTz('locked_until')->nullable();
            $table->timestampTz('created_at')->nullable();
            $table->index(['status', 'run_at']);
        });

        Schema::create('notification_outbox', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('channel', 30);
            $table->string('recipient_hash', 128);
            $table->string('template', 120);
            $table->string('payload_ref', 500);
            $table->string('status', 30);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('created_at');
            $table->timestampTz('sent_at')->nullable();
            $table->index(['status', 'created_at']);
        });

        Schema::create('idempotency_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('owner_scope', 160);
            $table->string('key', 200);
            $table->string('request_hash', 128);
            $table->unsignedSmallInteger('response_status');
            $table->jsonb('response_body')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('expires_at');
            $table->unique(['owner_scope', 'key']);
            $table->index('expires_at');
        });

        Schema::create('deletion_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('target_type', 120);
            $table->uuid('target_id');
            $table->uuid('requested_by')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->string('status', 30);
            $table->timestampTz('created_at');
            $table->foreign('requested_by')->references('id')->on('staff_users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('staff_users')->nullOnDelete();
            $table->index(['target_type', 'target_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deletion_requests');
        Schema::dropIfExists('idempotency_records');
        Schema::dropIfExists('notification_outbox');
        Schema::dropIfExists('jobs');
    }
};
