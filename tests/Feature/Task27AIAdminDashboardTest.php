<?php

use App\AIUsage;
use App\Services\AIControlService;
use App\AuditLog;
use App\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function task27Staff(string $role): StaffUser
{
    return StaffUser::factory()->active()->create(['role' => $role]);
}

it('shows AI usage controls only to administrators', function (): void {
    $reception = task27Staff('reception');
    $this->post(route('staff.login.store'), [
        'email' => $reception->email,
        'password' => 'test-password',
    ])->assertRedirect();

    $this->get(route('staff.ai.index'))->assertForbidden();
});

it('allows an administrator to view and update the AI budget and emergency switch', function (): void {
    AIUsage::factory()->create([
        'day' => now('UTC')->toDateString(),
        'feature' => 'assistant',
        'provider' => 'fake',
        'model' => 'fake-v1',
        'capability' => 'text',
        'input_tokens' => 10,
        'output_tokens' => 5,
        'estimated_cost_irr' => 2500,
        'request_count' => 1,
    ]);

    app(AIControlService::class)->settings()->update([
        'provider_token_limits' => ['fake-v1' => 15],
        'model_budget_limits_irr' => ['fake-v1' => 3_000],
    ]);

    $admin = task27Staff('admin');
    $this->post(route('staff.login.store'), [
        'email' => $admin->email,
        'password' => 'test-password',
    ])->assertRedirect();

    $this->get(route('staff.ai.index'))->assertOk()
        ->assertSee('مدیریت دستیار هوشمند')
        ->assertSee('10,000,000')
        ->assertSee('مصرف روزانه به تفکیک قابلیت')
        ->assertSee('assistant')
        ->assertSee('سرویس خارجی متصل نیست')
        ->assertSee('سقف داخلی توکن ماهانه')
        ->assertSee('سقف مالی ماهانه مدل')
        ->assertSee('هشدار مصرف توکن مدل fake-v1')
        ->assertSee('هشدار بودجه مدل fake-v1');

    $this->patch(route('staff.ai.update'), [
        'monthly_budget_irr' => 15_000_000,
        'emergency_disabled' => '1',
        'provider_token_limits' => ['fake-v1' => 1_000_000],
        'model_budget_limits_irr' => ['fake-v1' => 5_000_000],
    ])->assertRedirect();

    $this->assertDatabaseHas('ai_control_settings', [
        'monthly_budget_irr' => 15_000_000,
        'emergency_disabled' => true,
    ]);

    expect(\\App\\AIControlSetting::query()->firstOrFail()->provider_token_limits)->toBe(['fake-v1' => 1_000_000])
        ->and(\\App\\AIControlSetting::query()->firstOrFail()->model_budget_limits_irr)->toBe(['fake-v1' => 5_000_000]);

    expect(AuditLog::query()->where('action', 'ai.controls_updated')->count())->toBe(1);
});
