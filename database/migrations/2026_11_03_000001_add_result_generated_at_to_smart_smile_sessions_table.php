<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('smart_smile_sessions', function (Blueprint $table): void {
            $table->timestampTz('result_generated_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('smart_smile_sessions', function (Blueprint $table): void {
            $table->dropIndex(['result_generated_at']);
            $table->dropColumn('result_generated_at');
        });
    }
};
