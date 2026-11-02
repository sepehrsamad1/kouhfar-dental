<?php

declare(strict_types=1);

namespace App\Services;

use App\AIControlSetting;
use App\AIUsage;
use Illuminate\Support\Facades\DB;

final class AIControlService
{
    private const SETTINGS_ID = '00000000-0000-4000-8000-000000000027';

    public function settings(): AIControlSetting
    {
        return AIControlSetting::query()->firstOrCreate(
            ['id' => self::SETTINGS_ID],
            ['monthly_budget_irr' => 10_000_000, 'emergency_disabled' => false],
        );
    }

    public function monthlySpendIrr(): int
    {
        return (int) round((float) AIUsage::query()
            ->whereBetween('day', [now('UTC')->startOfMonth()->toDateString(), now('UTC')->endOfMonth()->toDateString()])
            ->sum('estimated_cost_irr'));
    }

    public function monthlyTokensForModel(string $model): int
    {
        return (int) AIUsage::query()
            ->where('model', $model)
            ->whereBetween('day', [now('UTC')->startOfMonth()->toDateString(), now('UTC')->endOfMonth()->toDateString()])
            ->selectRaw('COALESCE(SUM(input_tokens + output_tokens), 0) AS total')
            ->value('total');
    }

    public function monthlySpendForModel(string $model): int
    {
        return (int) round((float) AIUsage::query()
            ->where('model', $model)
            ->whereBetween('day', [now('UTC')->startOfMonth()->toDateString(), now('UTC')->endOfMonth()->toDateString()])
            ->sum('estimated_cost_irr'));
    }

    public function allowsRequests(?string $model = null): bool
    {
        $settings = $this->settings();

        if ($settings->emergency_disabled || $this->monthlySpendIrr() >= $settings->monthly_budget_irr) {
            return false;
        }

        if ($model !== null) {
            $tokenLimits = (array) $settings->provider_token_limits;
            $budgetLimits = (array) $settings->model_budget_limits_irr;
            $tokenLimit = (int) ($tokenLimits[$model] ?? 0);
            $budgetLimit = (int) ($budgetLimits[$model] ?? 0);

            if ($tokenLimit > 0 && $this->monthlyTokensForModel($model) >= $tokenLimit) {
                return false;
            }

            if ($budgetLimit > 0 && $this->monthlySpendForModel($model) >= $budgetLimit) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, mixed> */
    public function summary(): array
    {
        $settings = $this->settings();
        $spend = $this->monthlySpendIrr();
        $budget = max(1, (int) $settings->monthly_budget_irr);
        $percentage = (int) floor(($spend / $budget) * 100);
        $provider = (string) config('ai.capabilities.text.provider', 'fake');
        $modelLimits = [];

        $tokenLimits = (array) $settings->provider_token_limits;
        $budgetLimits = (array) $settings->model_budget_limits_irr;

        foreach ($this->configuredModels() as $model => $capabilities) {
            $tokenLimit = (int) ($tokenLimits[$model] ?? 0);
            $budgetLimit = (int) ($budgetLimits[$model] ?? 0);
            $tokensUsed = $this->monthlyTokensForModel($model);
            $modelSpend = $this->monthlySpendForModel($model);
            $tokenPercentage = $tokenLimit > 0 ? (int) floor(($tokensUsed / $tokenLimit) * 100) : null;
            $budgetPercentage = $budgetLimit > 0 ? (int) floor(($modelSpend / $budgetLimit) * 100) : null;

            $modelLimits[$model] = [
                'capabilities' => $capabilities,
                'tokens_used' => $tokensUsed,
                'token_limit' => $tokenLimit > 0 ? $tokenLimit : null,
                'token_percentage' => $tokenPercentage,
                'token_alerts' => $tokenPercentage === null ? [] : array_values(array_filter([80, 95], static fn (int $level): bool => $tokenPercentage >= $level)),
                'spend_irr' => $modelSpend,
                'budget_limit_irr' => $budgetLimit > 0 ? $budgetLimit : null,
                'budget_percentage' => $budgetPercentage,
                'budget_alerts' => $budgetPercentage === null ? [] : array_values(array_filter([50, 80, 100], static fn (int $level): bool => $budgetPercentage >= $level)),
                'blocked' => ($tokenLimit > 0 && $tokensUsed >= $tokenLimit) || ($budgetLimit > 0 && $modelSpend >= $budgetLimit),
            ];
        }

        return [
            'provider' => $provider,
            'model' => (string) config('ai.capabilities.text.model', 'fake-v1'),
            'provider_connection_status' => $provider === 'fake' ? 'fake' : 'unverified',
            'monthly_spend_irr' => $spend,
            'monthly_budget_irr' => $settings->monthly_budget_irr,
            'percentage' => $percentage,
            'alerts' => array_values(array_filter([50, 80, 100], static fn (int $level): bool => $percentage >= $level)),
            'blocked' => $settings->emergency_disabled || $spend >= $settings->monthly_budget_irr,
            'emergency_disabled' => $settings->emergency_disabled,
            'provider_quota_status' => 'unknown',
            'provider_quota_alert_percentages' => [80, 95],
            'model_limits' => $modelLimits,
        ];
    }

    /**
     * @param array<string, int> $providerTokenLimits
     * @param array<string, int> $modelBudgetLimitsIrr
     */
    public function update(
        int $monthlyBudgetIrr,
        bool $emergencyDisabled,
        array $providerTokenLimits = [],
        array $modelBudgetLimitsIrr = [],
    ): void {
        DB::transaction(function () use ($monthlyBudgetIrr, $emergencyDisabled, $providerTokenLimits, $modelBudgetLimitsIrr): void {
            $settings = AIControlSetting::query()->whereKey(self::SETTINGS_ID)->lockForUpdate()->firstOrFail();
            $settings->forceFill([
                'monthly_budget_irr' => $monthlyBudgetIrr,
                'emergency_disabled' => $emergencyDisabled,
                'provider_token_limits' => $providerTokenLimits,
                'model_budget_limits_irr' => $modelBudgetLimitsIrr,
            ])->save();
        });
    }

    /** @return array<string, list<string>> */
    private function configuredModels(): array
    {
        $models = [];

        foreach (['text', 'image_analysis', 'image_transformation'] as $capability) {
            $model = (string) config("ai.capabilities.{$capability}.model", '');

            if ($model !== '') {
                $models[$model] ??= [];
                $models[$model][] = $capability;
            }
        }

        return $models;
    }
}
