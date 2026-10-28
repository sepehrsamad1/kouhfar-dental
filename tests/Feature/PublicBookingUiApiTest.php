<?php

declare(strict_types=1);

use App\Appointment;
use App\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

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


it('fails closed when owner-required phone verification is not configured', function (): void {
    config(['booking.phone_verification_required' => true]);
    $service = Service::factory()->create(['is_active' => true, 'is_public_bookable' => true]);

    $this->withHeader('Idempotency-Key', 'verification-gate-test-0001')
        ->postJson('/api/v1/appointments', [
            'full_name' => 'Synthetic Booker',
            'mobile' => '09120000001',
            'service_id' => $service->id,
            'start_at' => '2026-10-29T09:30:00Z',
        ])
        ->assertStatus(503)
        ->assertJsonPath('code', 'PHONE_VERIFICATION_NOT_READY');
    expect(Appointment::query()->count())->toBe(0);
});

it('keeps staff-only modules and routes out of the public Vite bundle', function (): void {
    $manifestPath = public_path('build/manifest.json');
    expect(file_exists($manifestPath))->toBeTrue();
    $manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
    $seen = [];
    $visit = function (string $key) use (&$visit, &$seen, $manifest): void {
        if (isset($seen[$key]) || !isset($manifest[$key])) {
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
