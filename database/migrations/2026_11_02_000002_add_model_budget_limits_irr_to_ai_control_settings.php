<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_control_settings', function (Blueprint $table): void {
            $table->jsonb('model_budget_limits_irr')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ai_control_settings', function (Blueprint $table): void {
            $table->dropColumn('model_budget_limits_irr');
        });
    }
};
