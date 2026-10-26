<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kind', 40);
            $table->string('locale', 12);
            $table->jsonb('body');
            $table->string('status', 30);
            $table->timestampTz('published_at')->nullable();
            $table->timestampsTz();
            $table->unique(['kind', 'locale']);
            $table->index(['status', 'published_at']);
        });

        Schema::create('media_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('storage_key', 500)->unique();
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('byte_size');
            $table->string('sha256', 64);
            $table->uuid('owner_patient_id')->nullable();
            $table->string('kind', 40);
            $table->uuid('created_by')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('deleted_at')->nullable();
            $table->foreign('owner_patient_id')->references('id')->on('patients')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('staff_users')->nullOnDelete();
            $table->index(['owner_patient_id', 'deleted_at']);
            $table->index('sha256');
        });

        Schema::create('consent_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->string('scope', 120);
            $table->timestampTz('granted_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->string('document_ref', 500)->nullable();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->index(['patient_id', 'scope']);
        });

        Schema::create('ai_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('feature', 80);
            $table->string('actor_type', 40);
            $table->uuid('actor_id')->nullable();
            $table->string('status', 30);
            $table->timestampTz('created_at');
            $table->jsonb('content_ids')->nullable();
            $table->index(['feature', 'created_at']);
            $table->index(['actor_type', 'actor_id']);
        });

        Schema::create('ai_usage', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('day');
            $table->string('feature', 80);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('cost_estimate', 12, 4)->default(0);
            $table->unsignedInteger('request_count')->default(0);
            $table->unique(['day', 'feature']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage');
        Schema::dropIfExists('ai_requests');
        Schema::dropIfExists('consent_records');
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('content_items');
    }
};
