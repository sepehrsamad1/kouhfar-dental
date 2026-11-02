<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\AIRequest;
use App\StaffUser;
use App\AIUsage;
use App\Services\AIControlService;
use App\Support\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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

        $requests = AIRequest::query()
            ->selectRaw('provider, model, capability, status, COUNT(*) AS request_count')
            ->whereBetween('created_at', [now('UTC')->startOfMonth(), now('UTC')->endOfMonth()])
            ->groupBy('provider', 'model', 'capability', 'status')
            ->orderBy('provider')
            ->orderBy('model')
            ->get();

        return view('staff.ai.index', [
            'summary' => $controls->summary(),
            'settings' => $controls->settings(),
            'usage' => $usage,
            'requests' => $requests,
        ]);
    }

    public function update(Request $request, AIControlService $controls): RedirectResponse
    {
        Gate::authorize('manage-configuration');

        $data = $request->validate([
            'monthly_budget_irr' => ['required', 'integer', 'min:1000', 'max:1000000000000'],
            'emergency_disabled' => ['nullable', 'boolean'],
        ]);

        $setting = $controls->settings();
        $before = [
            'monthly_budget_irr' => $setting->monthly_budget_irr,
            'emergency_disabled' => $setting->emergency_disabled,
        ];
        $after = [
            'monthly_budget_irr' => (int) $data['monthly_budget_irr'],
            'emergency_disabled' => (bool) ($data['emergency_disabled'] ?? false),
        ];

        $controls->update($after['monthly_budget_irr'], $after['emergency_disabled']);
        $actor = $request->user('staff');
        abort_unless($actor instanceof StaffUser, 403);

        AuditLogger::record($actor, 'ai.controls_updated', $setting, $before, $after);

        return back()->with('success', 'تنظیمات بودجه و وضعیت اضطراری دستیار هوشمند ذخیره شد.');
    }
}
