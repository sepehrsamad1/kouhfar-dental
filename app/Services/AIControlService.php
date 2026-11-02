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

    public function allowsRequests(): bool
    {
        $settings = $this->settings();
        return ! $settings->emergency_disabled
            && $this->monthlySpendIrr() < $settings->monthly_budget_irr;
    }

    /** @return array<string, mixed> */
    public function summary(): array
    {
        $settings = $this->settings();
        $spend = $this->monthlySpendIrr();
        $budget = max(1, (int) $settings->monthly_budget_irr);
        $percentage = (int) floor(($spend / $budget) * 100);

        $provider = (string) config('ai.capabilities.text.provider', 'fake');

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
        ];
    }

    public function update(int $monthlyBudgetIrr, bool $emergencyDisabled): void
    {
        DB::transaction(function () use ($monthlyBudgetIrr, $emergencyDisabled): void {
            $settings = AIControlSetting::query()->whereKey(self::SETTINGS_ID)->lockForUpdate()->firstOrFail();
            $settings->forceFill([
                'monthly_budget_irr' => $monthlyBudgetIrr,
                'emergency_disabled' => $emergencyDisabled,
            ])->save();
        });
    }
}
