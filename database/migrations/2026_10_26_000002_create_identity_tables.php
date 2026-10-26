<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email', 320)->unique();
            $table->string('display_name', 200);
            $table->string('password_hash');
            $table->string('role', 40);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
        DB::statement('ALTER TABLE staff_users ALTER COLUMN email TYPE citext');

        Schema::create('sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('staff_users')->cascadeOnDelete();
            $table->string('token_hash', 128)->unique();
            $table->timestampTz('created_at');
            $table->timestampTz('expires_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampTz('last_seen_at')->nullable();
            $table->index(['user_id', 'expires_at']);
        });

        Schema::create('login_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('identifier_hash', 128);
            $table->string('ip_hash', 128);
            $table->timestampTz('created_at');
            $table->boolean('success');
            $table->index(['identifier_hash', 'created_at']);
            $table->index(['ip_hash', 'created_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('actor_id')->nullable();
            $table->string('action', 120);
            $table->string('target_type', 120)->nullable();
            $table->uuid('target_id')->nullable();
            $table->timestampTz('occurred_at');
            $table->uuid('request_id')->nullable();
            $table->jsonb('meta')->nullable();
            $table->index(['actor_id', 'occurred_at']);
            $table->index(['target_type', 'target_id']);
            $table->index('request_id');
            $table->foreign('actor_id')->references('id')->on('staff_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('login_attempts');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('staff_users');
    }
};
