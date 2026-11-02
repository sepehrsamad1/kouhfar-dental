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
        Schema::table('ai_usage', function (Blueprint $table): void {
            $table->string('provider', 80)->default('fake');
            $table->string('model', 120)->default('fake-v1');
            $table->string('capability', 40)->default('text');
            $table->decimal('estimated_cost_irr', 18, 2)->default(0);
            $table->decimal('actual_cost_irr', 18, 2)->nullable();
        });
        Schema::table('ai_usage', function (Blueprint $table): void {
            $table->dropUnique(['day', 'feature']);
            $table->unique(['day', 'feature', 'provider', 'model', 'capability'], 'ai_usage_model_daily_unique');
        });
        Schema::table('ai_requests', function (Blueprint $table): void {
            $table->string('provider', 80)->default('fake');
            $table->string('model', 120)->default('fake-v1');
            $table->string('capability', 40)->default('text');
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('estimated_cost_irr', 18, 2)->default(0);
            $table->decimal('actual_cost_irr', 18, 2)->nullable();
        });
        Schema::create('ai_control_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('monthly_budget_irr')->default(10000000);
            $table->boolean('emergency_disabled')->default(false);
            $table->jsonb('provider_token_limits')->nullable();
            $table->timestampsTz();
        });
        DB::table('ai_control_settings')->insert([
            'id' => '00000000-0000-4000-8000-000000000027',
            'monthly_budget_irr' => 10000000,
            'emergency_disabled' => false,
            'provider_token_limits' => null,
            'created_at' => now('UTC'),
            'updated_at' => now('UTC'),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_control_settings');
        Schema::table('ai_requests', function (Blueprint $table): void {
            $table->dropColumn(['provider', 'model', 'capability', 'input_tokens', 'output_tokens', 'estimated_cost_irr', 'actual_cost_irr']);
        });
        Schema::table('ai_usage', function (Blueprint $table): void {
            $table->dropUnique('ai_usage_model_daily_unique');
            $table->dropColumn(['provider', 'model', 'capability', 'estimated_cost_irr', 'actual_cost_irr']);
            $table->unique(['day', 'feature']);
        });
    }
};
