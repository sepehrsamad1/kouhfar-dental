<?php

declare(strict_types=1);

use App\VerificationChallenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['services.melipayamak.otp_url' => 'https://example.test/otp/test-token']);
});

it('sends an OTP through the configured provider and stores only a hash', function (): void {
    Http::fake([
        'example.test/otp/*' => Http::response(['code' => '3741437414', 'status' => 'ok'], 200),
    ]);

    $service = \\App\\Service::factory()->create(['is_active' => true, 'is_public_bookable' => true]);

    $response = $this->postJson('/api/v1/phone-verification/send', [
        'mobile' => '۰۹۱۲۳۴۵۶۷۸۹',
        'full_name' => 'Synthetic Booker',
        'service_id' => $service->id,
        'start_at' => '2026-11-10T09:30:00Z',
    ])->assertAccepted()
        ->assertJsonStructure(['challenge_id', 'expires_in', 'resend_after'])
        ->assertHeader('Cache-Control', 'no-store, private');

    Http::assertSent(fn ($request) => $request->url() === 'https://example.test/otp/test-token'
        && $request['to'] === '09123456789');

    $challenge = VerificationChallenge::query()->findOrFail($response->json('challenge_id'));
    expect($challenge->mobile_normalized)->toBe('09123456789')
        ->and(Hash::check('3741437414', $challenge->code_hash))->toBeTrue()
        ->and($challenge->code_hash)->not->toBe('3741437414')
        ->and($challenge->verified_at)->toBeNull();
});

it('verifies the OTP and returns a grant without exposing the OTP code', function (): void {
    Http::fake(['example.test/otp/*' => Http::response(['code' => '3741437414'], 200)]);
    $service = \\App\\Service::factory()->create(['is_active' => true, 'is_public_bookable' => true]);
    $sent = $this->postJson('/api/v1/phone-verification/send', [
        'mobile' => '09123456789',
        'full_name' => 'Synthetic Booker',
        'service_id' => $service->id,
        'start_at' => '2026-11-10T09:30:00Z',
    ])->assertAccepted();

    $verified = $this->postJson('/api/v1/phone-verification/verify', [
        'challenge_id' => $sent->json('challenge_id'),
        'code' => '3741437414',
    ])->assertOk()->assertJsonStructure(['verification_grant']);

    expect($verified->json('verification_grant'))->toBeString()->not->toBe('3741437414');
});

it('counts invalid OTP attempts and rejects the challenge after five attempts', function (): void {
    Http::fake(['example.test/otp/*' => Http::response(['code' => '3741437414'], 200)]);
    $service = \\App\\Service::factory()->create(['is_active' => true, 'is_public_bookable' => true]);
    $sent = $this->postJson('/api/v1/phone-verification/send', [
        'mobile' => '09123456789',
        'full_name' => 'Synthetic Booker',
        'service_id' => $service->id,
        'start_at' => '2026-11-10T09:30:00Z',
    ])->assertAccepted();

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson('/api/v1/phone-verification/verify', [
            'challenge_id' => $sent->json('challenge_id'),
            'code' => '0000000000',
        ])->assertUnprocessable()->assertJsonPath('code', 'OTP_INVALID_OR_EXPIRED');
    }

    expect(VerificationChallenge::query()->findOrFail($sent->json('challenge_id'))->attempts)->toBe(5);
});

it('fails closed when the provider is not configured', function (): void {
    config(['services.melipayamak.otp_url' => null]);
    $service = \\App\\Service::factory()->create(['is_active' => true, 'is_public_bookable' => true]);

    $this->postJson('/api/v1/phone-verification/send', [
        'mobile' => '09123456789',
        'full_name' => 'Synthetic Booker',
        'service_id' => $service->id,
        'start_at' => '2026-11-10T09:30:00Z',
    ])->assertServiceUnavailable()->assertJsonPath('code', 'OTP_SEND_FAILED');

    expect(VerificationChallenge::query()->count())->toBe(0);
});
