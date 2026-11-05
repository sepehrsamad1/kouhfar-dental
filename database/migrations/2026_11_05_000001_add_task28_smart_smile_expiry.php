<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('smart_smile_sessions', function (Blueprint $table): void {
            $table->timestampTz('token_expires_at')->nullable()->index();
            $table->timestampTz('result_expires_at')->nullable()->index();
        });

        DB::table('smart_smile_sessions')
            ->whereNull('token_expires_at')
            ->update(['token_expires_at' => DB::raw("consented_at + INTERVAL '24 hours'")]);

        DB::table('smart_smile_sessions')
            ->whereNotNull('result_generated_at')
            ->whereNull('result_expires_at')
            ->update(['result_expires_at' => DB::raw("result_generated_at + INTERVAL '12 months'")]);
    }

    public function down(): void
    {
        Schema::table('smart_smile_sessions', function (Blueprint $table): void {
            $table->dropIndex(['token_expires_at']);
            $table->dropIndex(['result_expires_at']);
            $table->dropColumn(['token_expires_at', 'result_expires_at']);
        });
    }
};
