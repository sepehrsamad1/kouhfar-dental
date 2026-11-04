<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_users', function (Blueprint $table): void {
            $table->string('mobile_normalized', 11)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('staff_users', function (Blueprint $table): void {
            $table->dropUnique(['mobile_normalized']);
            $table->dropColumn('mobile_normalized');
        });
    }
};
