<?php

declare(strict_types=1);

use App\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function task17Staff(array $attributes = []): StaffUser
{
    return StaffUser::factory()->active()->create(array_merge([
        'role' => 'reception',
    ], $attributes));
}

function authenticateTask17Staff(StaffUser $staff): void
{
    $staff->forceFill([
        'password_hash' => Hash::make('task17-password'),
    ])->save();

    test()->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'task17-password',
    ])->assertRedirect(route('staff.dashboard'));
}
it('protects the staff dashboard and applies no-store headers', function (): void {
    $response = $this->get(route('staff.dashboard'));

    $response->assertRedirect(route('staff.login'));

    $staff = task17Staff();

    authenticateTask17Staff($staff);

    $response = $this->get(route('staff.dashboard'));

    $response->assertOk()
        ->assertHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->assertHeader('Pragma', 'no-cache')
        ->assertSee('پنل کارکنان')
        ->assertDontSee('resources/css/app.css');
});

it('shows only authorized staff navigation', function (): void {
    $reception = task17Staff();

    authenticateTask17Staff($reception);

    $response = $this->get(route('staff.dashboard'));

    $response->assertDontSee(route('staff.configuration.index'))
        ->assertDontSee(route('staff.admin.audit'));

    $admin = task17Staff(['role' => 'admin']);

    authenticateTask17Staff($admin);

    $response = $this->get(route('staff.dashboard'));

    $response->assertSee(route('staff.configuration.index'))
        ->assertSee(route('staff.admin.audit'));
});

it('rejects an external intended URL after successful staff login', function (): void {
    $staff = task17Staff([
        'role' => 'admin',
        'email' => 'task17-admin@example.invalid',
    ]);
    $staff->forceFill([
        'password_hash' => Hash::make('task17-password'),
    ])->save();

    session()->put('url.intended', 'https://evilhacker.example/steal-data');

    $response = $this->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'task17-password',
    ]);

    $response->assertRedirect(route('staff.dashboard'));
});

it('keeps staff configuration and patient pages on the isolated staff bundle', function (): void {
    $staff = task17Staff(['role' => 'admin']);

    authenticateTask17Staff($staff);

    $this->get(route('staff.configuration.index'))
        ->assertOk()
        ->assertSee('staff-', false)
        ->assertDontSee('app-', false);

    $this->get(route('staff.patients.index'))
        ->assertOk()
        ->assertSee('staff-', false)
        ->assertDontSee('app-', false);
});
