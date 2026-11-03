<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deletion_requests', function (Blueprint $table): void {
            $table->uuid('reviewed_by')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->string('resolution_code', 60)->nullable();
            $table->foreign('reviewed_by')->references('id')->on('staff_users')->nullOnDelete();
            $table->index(['status', 'reviewed_at']);
            $table->index('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('deletion_requests', function (Blueprint $table): void {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['status', 'reviewed_at']);
            $table->dropIndex(['resolved_at']);
            $table->dropColumn(['reviewed_by', 'reviewed_at', 'resolved_at', 'resolution_code']);
        });
    }
};
