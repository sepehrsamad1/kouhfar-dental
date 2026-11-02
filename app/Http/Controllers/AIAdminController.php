<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\AIRequest;
use App\AIUsage;
use App\Services\AIControlService;
use App\StaffUser;
use App\Support\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class AIAdminController
{
    public function index(AIControlService $controls): View
    {
        Gate::authorize('manage-configuration');

        $from = now('UTC')->startOfMonth()->toDateString();
        $to = now('UTC')->endOfMonth()->toDateString();

        $usage = AIUsage::query()
            ->selectRaw('provider, model, capability, SUM(input_tokens) AS input_tokens, SUM(output_tokens) AS output_tokens, SUM(request_count) AS request_count, SUM(estimated_cost_irr) AS estimated_cost_irr, SUM(actual_cost_irr) AS actual_cost_irr')
            ->whereBetween('day', [$from, $to])
            ->groupBy('provider', 'model', 'capability')
            ->orderBy('provider')
            ->orderBy('model')
            ->get();

        $dailyUsage = AIUsage::query()
            ->selectRaw('day, feature, provider, model, capability, SUM(input_tokens) AS input_tokens, SUM(output_tokens) AS output_tokens, SUM(estimated_cost_irr) AS estimated_cost_irr, SUM(actual_cost_irr) AS actual_cost_irr, SUM(request_count) AS request_count')
            ->whereBetween('day', [now('UTC')->subDays(29)->toDateString(), $to])
            ->groupBy('day', 'feature', 'provider', 'model', 'capability')
            ->orderByDesc('day')
            ->orderBy('feature')
            ->limit(100)
            ->get();

        $requests = AIRequest::query()
            ->selectRaw('provider, model, capability, status, COUNT(*) AS request_count')
            ->whereBetween('created_at', [now('UTC')->startOfMonth(), now('UTC')->endOfMonth()])
            ->groupBy('provider', 'model', 'capability', 'status')
            ->orderBy('provider')
            ->orderBy('model')
            ->get();

        $recentRequests = AIRequest::query()
            ->select(['provider', 'model', 'capability', 'status', 'input_tokens', 'output_tokens', 'estimated_cost_irr', 'created_at'])
            ->latest('created_at')
            ->limit(100)
            ->get();

        return view('staff.ai.index', [
            'summary' => $controls->summary(),
            'settings' => $controls->settings(),
            'usage' => $usage,
            'dailyUsage' => $dailyUsage,
            'requests' => $requests,
            'recentRequests' => $recentRequests,
        ]);
    }

    public function update(Request $request, AIControlService $controls): RedirectResponse
    {
        Gate::authorize('manage-configuration');

        $data = $request->validate([
            'monthly_budget_irr' => ['required', 'integer', 'min:1000', 'max:1000000000000'],
            'emergency_disabled' => ['nullable', 'boolean'],
            'provider_token_limits' => ['sometimes', 'nullable', 'array'],
            'provider_token_limits.*' => ['nullable', 'integer', 'min:1', 'max:1000000000000'],
            'model_budget_limits_irr' => ['sometimes', 'nullable', 'array'],
            'model_budget_limits_irr.*' => ['nullable', 'integer', 'min:1000', 'max:1000000000000'],
        ]);

        $setting = $controls->settings();
        $allowedModels = collect(['text', 'image_analysis', 'image_transformation'])
            ->map(fn (string $capability): string => (string) config("ai.capabilities.{$capability}.model", ''))
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach (['provider_token_limits', 'model_budget_limits_irr'] as $field) {
            $unknownModels = array_diff(array_keys((array) ($data[$field] ?? [])), $allowedModels);

            if ($unknownModels !== []) {
                throw ValidationException::withMessages([$field => 'Only currently configured AI models may have limits.']);
            }
        }

        $tokenLimits = array_key_exists('provider_token_limits', $data)
            ? array_filter(array_map('intval', (array) $data['provider_token_limits']), static fn (int $value): bool => $value > 0)
            : (array) $setting->provider_token_limits;
        $modelBudgetLimits = array_key_exists('model_budget_limits_irr', $data)
            ? array_filter(array_map('intval', (array) $data['model_budget_limits_irr']), static fn (int $value): bool => $value > 0)
            : (array) $setting->model_budget_limits_irr;

        $before = [
            'monthly_budget_irr' => $setting->monthly_budget_irr,
            'emergency_disabled' => $setting->emergency_disabled,
            'provider_token_limits' => (array) $setting->provider_token_limits,
            'model_budget_limits_irr' => (array) $setting->model_budget_limits_irr,
        ];
        $after = [
            'monthly_budget_irr' => (int) $data['monthly_budget_irr'],
            'emergency_disabled' => (bool) ($data['emergency_disabled'] ?? false),
            'provider_token_limits' => $tokenLimits,
            'model_budget_limits_irr' => $modelBudgetLimits,
        ];

        $controls->update(
            $after['monthly_budget_irr'],
            $after['emergency_disabled'],
            $after['provider_token_limits'],
            $after['model_budget_limits_irr'],
        );
        $actor = $request->user('staff');
        abort_unless($actor instanceof StaffUser, 403);

        AuditLogger::record($actor, 'ai.controls_updated', $setting, $before, $after);

        return back()->with('success', 'تنظیمات بودجه و وضعیت اضطراری دستیار هوشمند ذخیره شد.');
    }
}
