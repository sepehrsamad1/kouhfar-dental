<?php

declare(strict_types=1);

use App\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns no-store availability and no slots for a service not approved for public booking', function (): void {
    $service = Service::factory()->create([
        'is_active' => false,
        'is_public_bookable' => false,
    ]);

    $this->get(route('booking.availability', $service))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('timezone', 'Asia/Tehran')
        ->assertJsonPath('slots', []);
});
