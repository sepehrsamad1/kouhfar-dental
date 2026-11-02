<?php

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
    $admin = task27Staff('admin');
    $this->post(route('staff.login.store'), [
        'email' => $admin->email,
        'password' => 'test-password',
    ])->assertRedirect();

    $this->get(route('staff.ai.index'))->assertOk()
        ->assertSee('مدیریت دستیار هوشمند')
        ->assertSee('10,000,000');

    $this->patch(route('staff.ai.update'), [
        'monthly_budget_irr' => 15_000_000,
        'emergency_disabled' => '1',
    ])->assertRedirect();

    $this->assertDatabaseHas('ai_control_settings', [
        'monthly_budget_irr' => 15_000_000,
        'emergency_disabled' => true,
    ]);

    expect(AuditLog::query()->where('action', 'ai.controls_updated')->count())->toBe(1);
});
