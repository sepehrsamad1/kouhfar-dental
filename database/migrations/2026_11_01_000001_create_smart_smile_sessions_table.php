<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_smile_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('consent_token_hash', 64)->unique();
            $table->string('consent_version', 40);
            $table->timestampTz('consented_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->uuid('media_asset_id')->nullable()->unique();
            $table->string('status', 30);
            $table->jsonb('result')->nullable();
            $table->timestampsTz();

            $table->foreign('media_asset_id')->references('id')->on('media_assets')->nullOnDelete();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_smile_sessions');
    }
};
