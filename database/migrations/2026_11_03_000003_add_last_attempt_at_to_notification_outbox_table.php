<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_outbox', function (Blueprint $table): void {
            $table->timestampTz('last_attempt_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('notification_outbox', function (Blueprint $table): void {
            $table->dropIndex(['last_attempt_at']);
            $table->dropColumn('last_attempt_at');
        });
    }
};
