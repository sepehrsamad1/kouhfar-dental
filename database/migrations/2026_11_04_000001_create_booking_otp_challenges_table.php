<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_otp_challenges', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->char('phone_hash', 64)->index();
            $table->char('ip_hash', 64)->index();
            $table->char('code_hash', 64)->nullable();
            $table->string('status', 24)->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('expires_at')->index();
            $table->timestampTz('last_sent_at')->nullable();
            $table->timestampTz('verified_at')->nullable();
            $table->timestampTz('consumed_at')->nullable();
            $table->timestampsTz();
            $table->index(['phone_hash', 'created_at']);
            $table->index(['ip_hash', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_otp_challenges');
    }
};
