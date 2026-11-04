<?php

declare(strict_types=1);

use App\Session as StaffSession;
use App\StaffUser;
use App\VerificationChallenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function makeStaffUser(array $attributes = []): StaffUser
{
    $staff = StaffUser::factory()->active()->create($attributes);
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

it('supports staff OTP login while preserving password login', function (): void {
    config(['services.melipayamak.otp_url' => 'https://example.test/otp/test-token']);
    Http::fake(['example.test/otp/*' => Http::response(['code' => '3741437414'], 200)]);

    $staff = makeStaffUser([
        'email' => 'otp-staff@example.invalid',
        'mobile_normalized' => '09123456789',
        'is_active' => true,
    ]);

    $this->from(route('staff.login'))->post(route('staff.login.otp.send'), ['mobile' => '09123456789'])
        ->assertRedirect(route('staff.login'))
        ->assertSessionHas('status');

    $challenge = VerificationChallenge::query()->findOrFail(session('staff_otp_challenge_id'));
    expect(Hash::check('3741437414', $challenge->code_hash))->toBeTrue()
        ->and($challenge->purpose)->toBe('staff_login');
    Http::assertSent(fn ($request) => $request->url() === 'https://example.test/otp/test-token'
        && $request['to'] === '09123456789');

    $this->from(route('staff.login'))->post(route('staff.login.otp.verify'), ['code' => '3741437414'])
        ->assertRedirect(route('staff.dashboard'));

    $this->assertAuthenticatedAs($staff, 'staff');
    expect(session('staff_registry_session_id'))->not->toBeNull();
    expect(StaffSession::query()->where('user_id', $staff->id)->exists())->toBeTrue();
});

it('does not reveal whether a staff OTP phone number exists', function (): void {
    Http::fake();

    $this->from(route('staff.login'))->post(route('staff.login.otp.send'), ['mobile' => '09120000099'])
        ->assertRedirect(route('staff.login'))
        ->assertSessionHas('status');

    Http::assertNothingSent();

    $this->from(route('staff.login'))
        ->post(route('staff.login.otp.verify'), ['code' => '123456'])
        ->assertRedirect(route('staff.login'))
        ->assertSessionHasErrors('code');

    $this->assertGuest('staff');
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
