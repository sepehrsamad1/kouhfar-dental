<?php

declare(strict_types=1);

use App\Session as StaffSession;
use App\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function makeStaffUser(array $attributes = []): StaffUser
{
    $staff = StaffUser::factory()->make($attributes);
    $staff->forceFill([
        'password_hash' => Hash::make('correct-password'),
    ])->save();

    return $staff->refresh();
}

beforeEach(function (): void {
    config()->set('session.driver', 'array');
    config()->set('staff_auth.login_throttle_attempts', 5);
    config()->set('staff_auth.login_throttle_seconds', 60);
    config()->set('staff_auth.idle_timeout_minutes', null);
    config()->set('staff_auth.absolute_timeout_minutes', null);
    RateLimiter::clear('staff-auth-test');
});

it('provides a staff login page and authenticates active staff', function (): void {
    $staff = makeStaffUser(['email' => 'staff@example.invalid', 'is_active' => true]);

    $response = $this->get(route('staff.login'));
    $response->assertOk();

    $response = $this->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'correct-password',
    ]);

    $response->assertRedirect(route('staff.dashboard'));
    $this->assertAuthenticatedAs($staff, 'staff');
    expect(session('staff_registry_session_id'))->not->toBeNull();
    expect(StaffSession::query()->where('user_id', $staff->id)->exists())->toBeTrue();
});

it('regenerates the session id after successful login', function (): void {
    $staff = makeStaffUser(['email' => 'fixation@example.invalid']);
    $this->get(route('staff.login'));
    $before = $this->app['session.store']->getId();

    $response = $this->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'correct-password',
    ]);

    $response->assertRedirect(route('staff.dashboard'));
    expect($this->app['session.store']->getId())->not->toBe($before);
});

it('rejects disabled staff with the same generic authentication error', function (): void {
    $staff = makeStaffUser([
        'email' => 'disabled@example.invalid',
        'is_active' => false,
    ]);

    $response = $this->from(route('staff.login'))->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'correct-password',
    ]);

    $response->assertRedirect(route('staff.login'));
    $response->assertSessionHasErrors('email');
    $this->assertGuest('staff');
});

it('uses a generic authentication error for an invalid password', function (): void {
    $staff = makeStaffUser(['email' => 'wrong@example.invalid']);

    $response = $this->from(route('staff.login'))->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect(route('staff.login'));
    $response->assertSessionHasErrors('email');
    $this->assertGuest('staff');
});

it('throttles repeated invalid staff logins without revealing account state', function (): void {
    $staff = makeStaffUser(['email' => 'throttle@example.invalid']);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $response = $this->from(route('staff.login'))->post(route('staff.login.store'), [
            'email' => $staff->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('staff.login'));
        $response->assertSessionHasErrors('email');
    }

    $response = $this->from(route('staff.login'))->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'correct-password',
    ]);

    $response->assertRedirect(route('staff.login'));
    $response->assertSessionHasErrors('email');
    $this->assertGuest('staff');
});

it('invalidates the authenticated session on logout', function (): void {
    $staff = makeStaffUser(['email' => 'logout@example.invalid']);
    $this->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'correct-password',
    ])->assertRedirect(route('staff.dashboard'));

    $registryId = session('staff_registry_session_id');

    $this->post(route('staff.logout'))->assertRedirect(route('staff.login'));

    $this->assertGuest('staff');
    expect(StaffSession::query()->find($registryId)->revoked_at)->not->toBeNull();
});

it('enforces an approved idle timeout when configured', function (): void {
    config()->set('staff_auth.idle_timeout_minutes', 1);

    $staff = makeStaffUser(['email' => 'idle@example.invalid']);
    $this->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'correct-password',
    ]);

    $registryId = session('staff_registry_session_id');
    session(['staff_last_activity_at' => now('UTC')->subMinutes(2)->timestamp]);

    $response = $this->get(route('staff.dashboard'));

    $response->assertRedirect(route('staff.login'));
    $this->assertGuest('staff');
    expect(StaffSession::query()->find($registryId)->revoked_at)->not->toBeNull();
});

it('enforces an approved absolute timeout when configured', function (): void {
    config()->set('staff_auth.absolute_timeout_minutes', 1);

    $staff = makeStaffUser(['email' => 'absolute@example.invalid']);
    $this->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'correct-password',
    ]);

    $registryId = session('staff_registry_session_id');
    session(['staff_authenticated_at' => now('UTC')->subMinutes(2)->timestamp]);

    $response = $this->get(route('staff.dashboard'));

    $response->assertRedirect(route('staff.login'));
    $this->assertGuest('staff');
    expect(StaffSession::query()->find($registryId)->revoked_at)->not->toBeNull();
});

it('keeps passwords out of staff authentication responses and schema output', function (): void {
    $staff = makeStaffUser(['email' => 'hidden@example.invalid']);

    expect($staff->toArray())->not->toHaveKey('password_hash')
        ->and($staff->getAuthPassword())->not->toBeNull()
        ->and(Schema::hasColumn('staff_users', 'password_hash'))->toBeTrue();
});
