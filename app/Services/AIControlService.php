<?php

declare(strict_types=1);

namespace App\Services;

use App\AIControlSetting;
use App\AIRequest;
use Illuminate\Support\Collection;
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
        $spend = AIRequest::query()
            ->whereBetween('created_at', [now('UTC')->startOfMonth(), now('UTC')->endOfMonth()])
            ->whereIn('status', ['succeeded'])
            ->selectRaw('COALESCE(SUM(COALESCE(actual_cost_irr, estimated_cost_irr)), 0) AS spend')
            ->value('spend');

        return (int) round((float) $spend);
    }

    public function monthlyTokensForModel(string $model): int
    {
        return (int) AIRequest::query()
            ->where('model', $model)
            ->whereBetween('created_at', [now('UTC')->startOfMonth(), now('UTC')->endOfMonth()])
            ->selectRaw('COALESCE(SUM(input_tokens + output_tokens), 0) AS total')
            ->value('total');
    }

    public function monthlySpendForModel(string $model): int
    {
        $spend = AIRequest::query()
            ->where('model', $model)
            ->whereBetween('created_at', [now('UTC')->startOfMonth(), now('UTC')->endOfMonth()])
            ->selectRaw('COALESCE(SUM(COALESCE(actual_cost_irr, estimated_cost_irr)), 0) AS spend')
            ->value('spend');

        return (int) round((float) $spend);
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
        $credentialsConfigured = is_string(config('ai.capabilities.text.api_key'))
            && trim((string) config('ai.capabilities.text.api_key')) !== '';
        $modelLimits = [];
        $modelUsage = $this->monthlyModelUsage();

        $tokenLimits = (array) $settings->provider_token_limits;
        $budgetLimits = (array) $settings->model_budget_limits_irr;

        foreach ($this->configuredModels() as $model => $capabilities) {
            $tokenLimit = (int) ($tokenLimits[$model] ?? 0);
            $budgetLimit = (int) ($budgetLimits[$model] ?? 0);
            $usage = $modelUsage->get($model);
            $tokensUsed = (int) ($usage?->tokens_used ?? 0);
            $modelSpend = (int) round((float) ($usage?->spend_irr ?? 0));
            $tokenPercentage = $tokenLimit > 0 ? (int) floor(($tokensUsed / $tokenLimit) * 100) : null;
            $budgetPercentage = $budgetLimit > 0 ? (int) floor(($modelSpend / $budgetLimit) * 100) : null;

            $providers = array_values(array_unique(array_map(
                static fn (string $capability): string => (string) config("ai.capabilities.{$capability}.provider", 'fake'),
                $capabilities,
            )));
            $modelCredentialsConfigured = true;

            foreach ($capabilities as $capability) {
                $capabilityProvider = (string) config("ai.capabilities.{$capability}.provider", 'fake');
                $capabilityKey = config("ai.capabilities.{$capability}.api_key");

                if ($capabilityProvider !== 'fake' && (! is_string($capabilityKey) || trim($capabilityKey) === '')) {
                    $modelCredentialsConfigured = false;
                }
            }

            $modelPricing = (array) config('ai.pricing_irr.models', []);
            $pricingConfigured = true;

            foreach ($capabilities as $capability) {
                $rates = array_key_exists($model, $modelPricing)
                    ? (array) $modelPricing[$model]
                    : (array) config("ai.pricing_irr.{$capability}", []);

                if ((float) ($rates['input_per_1k'] ?? 0) + (float) ($rates['output_per_1k'] ?? 0) <= 0) {
                    $pricingConfigured = false;
                }
            }

            $modelLimits[$model] = [
                'capabilities' => $capabilities,
                'providers' => $providers,
                'credentials_configured' => $modelCredentialsConfigured,
                'pricing_configured' => $pricingConfigured,
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
            'provider_credentials_configured' => $credentialsConfigured,
            'model' => (string) config('ai.capabilities.text.model', 'fake-v1'),
            'provider_connection_status' => $provider === 'fake' ? 'fake' : ($credentialsConfigured ? 'unverified' : 'missing_credentials'),
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

    /**
     * @return Collection<string, object{tokens_used: int, spend_irr: float}>
     */
    private function monthlyModelUsage(): Collection
    {
        return AIRequest::query()
            ->selectRaw('model, COALESCE(SUM(input_tokens + output_tokens), 0) AS tokens_used, COALESCE(SUM(COALESCE(actual_cost_irr, estimated_cost_irr)), 0) AS spend_irr')
            ->whereBetween('created_at', [now('UTC')->startOfMonth(), now('UTC')->endOfMonth()])
            ->groupBy('model')
            ->get()
            ->keyBy('model');
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
