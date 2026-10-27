<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_exceptions', function (Blueprint $table): void {
            $table->uuid('practitioner_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Clinic-wide exceptions cannot be represented by the original non-null column.
        // Refuse rollback while such rows exist rather than silently deleting scope.
        if (Schema::hasTable('schedule_exceptions')
            && DB::table('schedule_exceptions')->whereNull('practitioner_id')->exists()) {
            throw new RuntimeException('Cannot make schedule_exceptions.practitioner_id non-null while clinic-wide exceptions exist.');
        }

        Schema::table('schedule_exceptions', function (Blueprint $table): void {
            $table->uuid('practitioner_id')->nullable(false)->change();
        });
    }
};
