<?php

declare(strict_types=1);

use App\Appointment;
use App\BookingOtpChallenge;
use App\Service;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('lists only active public-bookable services with the minimal public shape', function (): void {
    $public = Service::factory()->create([
        'name_fa' => 'مشاوره آزمایشی', 'is_active' => true, 'is_public_bookable' => true,
    ]);
    Service::factory()->create(['is_active' => false, 'is_public_bookable' => true]);
    Service::factory()->create(['is_active' => true, 'is_public_bookable' => false]);

    $response = $this->getJson('/api/v1/services')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    $response->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $public->id)
        ->assertJsonPath('data.0.name_fa', 'مشاوره آزمایشی');
    expect(array_keys($response->json('data.0')))->toBe(['id', 'name_fa', 'duration_minutes']);
});

it('returns date-scoped availability without exposing practitioner or resource identifiers', function (): void {
    $service = Service::factory()->create(['is_active' => false, 'is_public_bookable' => false]);

    $response = $this->getJson('/api/v1/availability/'.$service->id.'?date=2026-10-29')
        ->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('data.date', '2026-10-29')
        ->assertJsonPath('data.timezone', 'Asia/Tehran')
        ->assertJsonPath('data.slots', []);
    expect(json_encode($response->json(), JSON_THROW_ON_ERROR))
        ->not->toContain('practitioner_id')
        ->not->toContain('resource_id');
});

it('validates the availability date as an ISO clinic-local date', function (): void {
    $service = Service::factory()->create();
    $this->getJson('/api/v1/availability/'.$service->id.'?date=not-a-date')->assertUnprocessable();
});

it('renders a same-origin CSRF token for the public booking POST', function (): void {
    $this->get('/')->assertOk()->assertSee('name="csrf-token"', false);
});

it('fails closed when a public booking omits its verified OTP challenge', function (): void {
    config(['booking.phone_verification_required' => true]);
    $service = Service::factory()->create(['is_active' => true, 'is_public_bookable' => true]);

    $this->withHeader('Idempotency-Key', 'verification-gate-test-0001')
        ->postJson('/api/v1/appointments', [
            'full_name' => 'Synthetic Booker',
            'mobile' => '09120000001',
            'service_id' => $service->id,
            'start_at' => '2026-10-29T09:30:00Z',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['otp_challenge_id']);

    expect(Appointment::query()->count())->toBe(0);
});

it('keeps booking SMS disabled by default and fails closed without a provider', function (): void {
    config(['notifications.otp.driver' => 'null']);
    Http::fake();

    $this->postJson('/api/v1/booking-otp/issue', ['mobile' => '09120000001'])
        ->assertStatus(503)
        ->assertJsonPath('code', 'OTP_UNAVAILABLE');

    expect(BookingOtpChallenge::query()->where('status', 'delivery_failed')->count())->toBe(1);
    Http::assertNothingSent();
});

it('rejects a state-changing public booking request without a valid CSRF token', function (): void {
    $service = Service::factory()->create(['is_active' => true, 'is_public_bookable' => true]);

    $this->withMiddleware(PreventRequestForgery::class)
        ->withHeader('Idempotency-Key', 'csrf-negative-test-0001')
        ->post('/api/v1/appointments', [
            'full_name' => 'Synthetic Booker',
            'mobile' => '09120000001',
            'service_id' => $service->id,
            'start_at' => '2026-10-29T09:30:00Z',
        ])
        ->assertStatus(419);

    expect(Appointment::query()->count())->toBe(0);
});

it('rejects a booking OTP issuance request without a valid CSRF token', function (): void {
    $this->withMiddleware(PreventRequestForgery::class)
        ->post('/api/v1/booking-otp/issue', ['mobile' => '09120000001'])
        ->assertStatus(419);

    expect(BookingOtpChallenge::query()->count())->toBe(0);
});

it('keeps staff-only modules and routes out of the public Vite bundle', function (): void {
    $manifestPath = public_path('build/manifest.json');
    expect(file_exists($manifestPath))->toBeTrue();
    $manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
    $seen = [];
    $visit = function (string $key) use (&$visit, &$seen, $manifest): void {
        if (isset($seen[$key])) {
            return;
        }
        if (array_key_exists($key, $manifest) === false) {
            return;
        }
        $seen[$key] = $manifest[$key];
        foreach (array_merge($manifest[$key]['imports'] ?? [], $manifest[$key]['dynamicImports'] ?? []) as $import) {
            $visit($import);
        }
    };
    $visit('resources/js/app.js');
    expect($seen)->not->toBeEmpty();
    foreach (array_keys($seen) as $source) {
        expect($source)->not->toMatch('#resources/js/(staff|admin)/#');
    }
    foreach ($seen as $asset) {
        $content = file_get_contents(public_path('build/'.$asset['file']));
        expect($content)->not->toContain('/api/v1/staff')
            ->not->toContain('/api/v1/admin');
    }
});
