<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_challenges', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('mobile_normalized', 11);
            $table->string('purpose', 40);
            $table->string('intent_hash', 64);
            $table->string('code_hash', 255);
            $table->string('grant_hash', 64)->nullable()->unique();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('expires_at');
            $table->timestampTz('verified_at')->nullable();
            $table->timestampTz('consumed_at')->nullable();
            $table->string('consumed_key_hash', 64)->nullable();
            $table->timestampsTz();
            $table->index(['mobile_normalized', 'purpose', 'created_at']);
            $table->index(['purpose', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_challenges');
    }
};
