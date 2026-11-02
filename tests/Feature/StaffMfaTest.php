<?php

declare(strict_types=1);

use App\Services\TotpService;
use App\StaffUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function task28TotpCode(string $secret, int $timestamp): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';

    foreach (str_split($secret) as $character) {
        $bits .= str_pad(decbin(strpos($alphabet, $character)), 5, '0', STR_PAD_LEFT);
    }

    $key = '';
    foreach (str_split($bits, 8) as $chunk) {
        if (strlen($chunk) === 8) {
            $key .= chr(bindec($chunk));
        }
    }

    $counter = intdiv($timestamp, 30);
    $message = pack('N2', ($counter >> 32) & 0xffffffff, $counter & 0xffffffff);
    $hash = hash_hmac('sha1', $message, $key, true);
    $offset = ord($hash[strlen($hash) - 1]) & 0x0f;
    $binary = unpack('N', substr($hash, $offset, 4))[1] & 0x7fffffff;

    return str_pad((string) ($binary % 1_000_000), 6, '0', STR_PAD_LEFT);
}

function task28MfaStaff(array $attributes = []): StaffUser
{
    $staff = StaffUser::factory()->active()->create($attributes);
    $staff->forceFill(['password_hash' => Hash::make('correct-password')])->save();

    return $staff->refresh();
}

beforeEach(function (): void {
    config()->set('session.driver', 'array');
    config()->set('staff_auth.mfa_required', true);
    config()->set('staff_auth.mfa_pending_timeout_minutes', 10);
    config()->set('staff_auth.login_throttle_attempts', 5);
});

it('matches the RFC 6238 SHA-1 six-digit test vector', function (): void {
    $totp = new TotpService;

    expect($totp->verify('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '287082', 59, 0))->toBeTrue()
        ->and($totp->verify('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '287083', 59, 0))->toBeFalse();
});

it('requires TOTP enrollment before the first staff login and shows recovery codes once', function (): void {
    $staff = task28MfaStaff(['email' => 'first-login@example.invalid']);

    $this->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'correct-password',
    ])->assertRedirect(route('staff.mfa.setup'));

    $this->assertGuest('staff');
    $staff->refresh();
    expect($staff->mfa_enabled_at)->toBeNull()
        ->and($staff->mfa_totp_secret)->not->toBeEmpty()
        ->and($staff->getRawOriginal('mfa_totp_secret'))->not->toBe($staff->mfa_totp_secret)
        ->and($staff->toArray())->not->toHaveKey('mfa_totp_secret');

    $this->get(route('staff.mfa.setup'))->assertOk()->assertSee($staff->mfa_totp_secret);

    $code = task28TotpCode($staff->mfa_totp_secret, now('UTC')->timestamp);
    $response = $this->post(route('staff.mfa.setup.complete'), ['code' => $code]);

    $response->assertOk()
        ->assertSee('کدهای بازیابی')
        ->assertHeader('Cache-Control', 'no-store, private');
    $this->assertAuthenticatedAs($staff->fresh(), 'staff');
    expect($staff->fresh()->mfa_enabled_at)->not->toBeNull()
        ->and(count($staff->fresh()->mfa_recovery_codes))->toBe(10);
    expect(AuditLog::query()->where('action', 'staff.mfa.enabled')->exists())->toBeTrue();

    expect(session('staff_mfa_recovery_codes'))->toBeNull();
    $this->get(route('staff.mfa.recovery-codes'))->assertRedirect(route('staff.dashboard'));
});

it('requires a valid TOTP challenge for enrolled staff', function (): void {
    $staff = task28MfaStaff(['email' => 'totp-login@example.invalid']);
    $secret = app(TotpService::class)->generateSecret();
    $staff->forceFill([
        'mfa_totp_secret' => $secret,
        'mfa_enabled_at' => now('UTC'),
        'mfa_recovery_codes' => [],
    ])->save();

    $this->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'correct-password',
    ])->assertRedirect(route('staff.mfa.challenge'));

    $this->assertGuest('staff');
    $this->get(route('staff.mfa.challenge'))->assertOk();

    $code = task28TotpCode($secret, now('UTC')->timestamp);
    $this->post(route('staff.mfa.verify'), ['code' => $code])
        ->assertRedirect(route('staff.dashboard'));

    $this->assertAuthenticatedAs($staff->fresh(), 'staff');
});

it('consumes a recovery code once and rejects reuse', function (): void {
    $staff = task28MfaStaff(['email' => 'recovery@example.invalid']);
    $secret = app(TotpService::class)->generateSecret();
    $recovery = 'ABCD1234EFGH';
    $staff->forceFill([
        'mfa_totp_secret' => $secret,
        'mfa_enabled_at' => now('UTC'),
        'mfa_recovery_codes' => [Hash::make($recovery)],
    ])->save();

    $this->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'correct-password',
    ])->assertRedirect(route('staff.mfa.challenge'));

    $this->post(route('staff.mfa.verify'), ['code' => $recovery])
        ->assertRedirect(route('staff.dashboard'));

    expect($staff->fresh()->mfa_recovery_codes)->toBe([])
        ->and(AuditLog::query()->where('action', 'staff.mfa.recovery_code_used')->exists())->toBeTrue();
    $this->post(route('staff.logout'))->assertRedirect(route('staff.login'));

    $this->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'correct-password',
    ])->assertRedirect(route('staff.mfa.challenge'));

    $this->from(route('staff.mfa.challenge'))
        ->post(route('staff.mfa.verify'), ['code' => $recovery])
        ->assertRedirect(route('staff.mfa.challenge'))
        ->assertSessionHasErrors('code');

    $this->assertGuest('staff');
});

it('rejects reuse of the same TOTP time-step after a successful login', function (): void {
    $staff = task28MfaStaff(['email' => 'totp-replay@example.invalid']);
    $secret = app(TotpService::class)->generateSecret();
    $counter = intdiv(now('UTC')->timestamp, 30);
    $staff->forceFill([
        'mfa_totp_secret' => $secret,
        'mfa_enabled_at' => now('UTC'),
        'mfa_last_totp_counter' => $counter - 1,
        'mfa_recovery_codes' => [],
    ])->save();

    $code = task28TotpCode($secret, now('UTC')->timestamp);
    $this->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'correct-password',
    ])->assertRedirect(route('staff.mfa.challenge'));
    $this->post(route('staff.mfa.verify'), ['code' => $code])
        ->assertRedirect(route('staff.dashboard'));
    $this->post(route('staff.logout'))->assertRedirect(route('staff.login'));

    $this->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'correct-password',
    ])->assertRedirect(route('staff.mfa.challenge'));
    $this->from(route('staff.mfa.challenge'))
        ->post(route('staff.mfa.verify'), ['code' => $code])
        ->assertRedirect(route('staff.mfa.challenge'))
        ->assertSessionHasErrors('code');

    $this->assertGuest('staff');
});

it('expires a pending MFA challenge after ten minutes', function (): void {
    $staff = task28MfaStaff(['email' => 'expired-mfa@example.invalid']);
    $staff->forceFill([
        'mfa_totp_secret' => app(TotpService::class)->generateSecret(),
        'mfa_enabled_at' => now('UTC'),
        'mfa_recovery_codes' => [],
    ])->save();

    $this->post(route('staff.login.store'), [
        'email' => $staff->email,
        'password' => 'correct-password',
    ])->assertRedirect(route('staff.mfa.challenge'));

    session(['staff_mfa_pending_at' => now('UTC')->subMinutes(11)->timestamp]);

    $this->get(route('staff.mfa.challenge'))->assertRedirect(route('staff.login'));
    $this->assertGuest('staff');
});
