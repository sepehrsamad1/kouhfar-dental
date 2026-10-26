<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practitioners', function (Blueprint $table): void {
            $table->uuid('staff_user_id')->nullable()->unique();
            $table->foreign('staff_user_id')->references('id')->on('staff_users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('practitioners', function (Blueprint $table): void {
            $table->dropForeign(['staff_user_id']);
            $table->dropUnique(['staff_user_id']);
            $table->dropColumn('staff_user_id');
        });
    }
};
