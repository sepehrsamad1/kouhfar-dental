<?php

declare(strict_types=1);

use App\Session as StaffSession;
use App\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function createActiveStaffUser(array $attributes = []): StaffUser
{
    return StaffUser::factory()->active()->create($attributes);
}

it('shows the staff login page', function () {
    $this->get(route('staff.login'))
        ->assertOk();
});

it('authenticates an active staff user', function () {
    $staff = createActiveStaffUser([
        'email' => 'staff@example.com',
        'password_hash' => Hash::make('test-password'),
    ]);

    $this->post(route('staff.login.store'), [
        'email' => 'staff@example.com',
        'password' => 'test-password',
    ])->assertRedirect(route('staff.dashboard'));

    $this->assertAuthenticatedAs($staff, 'staff');

    $this->assertDatabaseHas('sessions', [
        'user_id' => $staff->id,
    ]);
});

it('regenerates the session id after successful login', function () {
    $staff = createActiveStaffUser([
        'password_hash' => Hash::make('test-password'),
    ]);

    $oldSessionId = session()->getId();

    $this->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'test-password',
    ]);

    expect(session()->getId())->not->toBe($oldSessionId);
});

it('redirects to a same-origin intended URL after successful login', function () {
    $staff = createActiveStaffUser([
        'email' => 'redirect@example.com',
        'password_hash' => Hash::make('test-password'),
    ]);

    $intendedUrl = url('/staff/dashboard?section=appointments');

    $this->withSession([
        'url.intended' => $intendedUrl,
    ])->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'test-password',
    ])->assertRedirect($intendedUrl);
});

it('rejects an external intended URL after successful login', function () {
    $staff = createActiveStaffUser([
        'email' => 'external@example.com',
        'password_hash' => Hash::make('test-password'),
    ]);

    $this->withSession([
        'url.intended' => 'https://evil.example/phishing',
    ])->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'test-password',
    ])->assertRedirect(route('staff.dashboard'));
});

it('rejects a protocol relative external intended URL', function () {
    $staff = createActiveStaffUser([
        'email' => 'protocol@example.com',
        'password_hash' => Hash::make('test-password'),
    ]);

    $this->withSession([
        'url.intended' => '//evil.example/phishing',
    ])->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'test-password',
    ])->assertRedirect(route('staff.dashboard'));
});

it('does not authenticate a disabled staff user', function () {
    $staff = StaffUser::factory()->create([
        'email' => 'disabled@example.com',
        'password_hash' => Hash::make('test-password'),
        'is_active' => false,
    ]);

    $this->from(route('staff.login'))
        ->post(route('staff.login.store'), [
            'email' => $staff->email,
            'password' => 'test-password',
        ])
        ->assertRedirect(route('staff.login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest('staff');
});

it('rejects an incorrect password', function () {
    $staff = createActiveStaffUser([
        'password_hash' => Hash::make('correct-password'),
    ]);

    $this->from(route('staff.login'))
        ->post(route('staff.login.store'), [
            'email' => $staff->email,
            'password' => 'incorrect-password',
        ])
        ->assertRedirect(route('staff.login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest('staff');
});

it('throttles repeated invalid staff logins', function () {
    $staff = createActiveStaffUser([
        'email' => 'throttle@example.com',
        'password_hash' => Hash::make('correct-password'),
    ]);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('staff.login.store'), [
            'email' => $staff->email,
            'password' => 'wrong-password',
        ]);
    }

    $this->from(route('staff.login'))
        ->post(route('staff.login.store'), [
            'email' => $staff->email,
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('staff.login'))
        ->assertSessionHasErrors('email');
});

it('records a successful login attempt', function () {
    $staff = createActiveStaffUser([
        'email' => 'success@example.com',
        'password_hash' => Hash::make('test-password'),
    ]);

    $this->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'test-password',
    ]);

    $this->assertDatabaseHas('login_attempts', [
        'identifier_hash' => hash('sha256', strtolower($staff->email)),
        'ip_hash' => hash('sha256', '127.0.0.1'),
        'success' => true,
    ]);
});

it('records a failed login attempt', function () {
    $staff = createActiveStaffUser([
        'email' => 'failure@example.com',
        'password_hash' => Hash::make('correct-password'),
    ]);

    $this->from(route('staff.login'))
        ->post(route('staff.login.store'), [
            'email' => $staff->email,
            'password' => 'wrong-password',
        ]);

    $this->assertDatabaseHas('login_attempts', [
        'identifier_hash' => hash('sha256', strtolower($staff->email)),
        'success' => false,
    ]);
});

it('invalidates the staff session on logout', function () {
    $staff = createActiveStaffUser();

    $this->actingAs($staff, 'staff');

    $this->post(route('staff.logout'))
        ->assertRedirect(route('staff.login'));

    $this->assertGuest('staff');
});

it('expires an idle staff session when idle timeout is configured', function () {
    config(['staff_auth.idle_timeout_minutes' => 1]);

    $staff = createActiveStaffUser();

    $this->actingAs($staff, 'staff');

    $session = StaffSession::create([
        'user_id' => $staff->id,
        'token_hash' => hash('sha256', 'test-session-token'),
        'created_at' => now()->subMinutes(5),
        'expires_at' => now()->addHour(),
        'last_seen_at' => now()->subMinutes(2),
    ]);

    session([
        'staff_registry_session_id' => $session->id,
        'staff_authenticated_at' => now()->subMinutes(5)->timestamp,
        'staff_last_activity_at' => now()->subMinutes(2)->timestamp,
    ]);

    $this->get(route('staff.dashboard'))
        ->assertRedirect(route('staff.login'));

    $this->assertGuest('staff');

    $this->assertNotNull($session->fresh()->revoked_at);
});

it('expires an absolute staff session when absolute timeout is configured', function () {
    config(['staff_auth.absolute_timeout_minutes' => 1]);

    $staff = createActiveStaffUser();

    $this->actingAs($staff, 'staff');

    $session = StaffSession::create([
        'user_id' => $staff->id,
        'token_hash' => hash('sha256', 'absolute-session-token'),
        'created_at' => now()->subMinutes(5),
        'expires_at' => now()->addHour(),
        'last_seen_at' => now(),
    ]);

    session([
        'staff_registry_session_id' => $session->id,
        'staff_authenticated_at' => now()->subMinutes(5)->timestamp,
        'staff_last_activity_at' => now()->timestamp,
    ]);

    $this->get(route('staff.dashboard'))
        ->assertRedirect(route('staff.login'));

    $this->assertGuest('staff');

    $this->assertNotNull($session->fresh()->revoked_at);
});

it('expires a staff session when its registry record does not exist', function () {
    $staff = createActiveStaffUser();

    $this->actingAs($staff, 'staff');

    session([
        'staff_registry_session_id' => '00000000-0000-4000-8000-000000000000',
        'staff_authenticated_at' => now()->timestamp,
        'staff_last_activity_at' => now()->timestamp,
    ]);

    $this->get(route('staff.dashboard'))
        ->assertRedirect(route('staff.login'));

    $this->assertGuest('staff');
});

it('expires a staff session when its registry has been revoked', function () {
    $staff = createActiveStaffUser();

    $session = StaffSession::create([
        'user_id' => $staff->id,
        'token_hash' => hash('sha256', 'revoked-session-token'),
        'created_at' => now()->subMinutes(5),
        'expires_at' => now()->addHour(),
        'revoked_at' => now()->subMinute(),
        'last_seen_at' => now(),
    ]);

    $this->actingAs($staff, 'staff');

    session([
        'staff_registry_session_id' => $session->id,
        'staff_authenticated_at' => now()->timestamp,
        'staff_last_activity_at' => now()->timestamp,
    ]);

    $this->get(route('staff.dashboard'))
        ->assertRedirect(route('staff.login'));

    $this->assertGuest('staff');

    $this->assertNotNull($session->fresh()->revoked_at);
});

it('expires a staff session when its registry belongs to another user without revoking that registry', function () {
    $authenticatedStaff = createActiveStaffUser();
    $registryOwner = createActiveStaffUser();

    $session = StaffSession::create([
        'user_id' => $registryOwner->id,
        'token_hash' => hash('sha256', 'other-user-session-token'),
        'created_at' => now()->subMinutes(5),
        'expires_at' => now()->addHour(),
        'last_seen_at' => now(),
    ]);

    $this->actingAs($authenticatedStaff, 'staff');

    session([
        'staff_registry_session_id' => $session->id,
        'staff_authenticated_at' => now()->timestamp,
        'staff_last_activity_at' => now()->timestamp,
    ]);

    $this->get(route('staff.dashboard'))
        ->assertRedirect(route('staff.login'));

    $this->assertGuest('staff');

    $this->assertDatabaseHas('sessions', [
        'id' => $session->id,
        'user_id' => $registryOwner->id,
        'revoked_at' => null,
    ]);
});

it('does not expose password hashes in staff user array output', function () {
    $staff = createActiveStaffUser();

    expect($staff->toArray())->not->toHaveKey('password_hash');
});
