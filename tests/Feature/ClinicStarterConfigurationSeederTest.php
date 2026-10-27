<?php

declare(strict_types=1);

use App\Practitioner;
use App\Resource;
use App\Service;
use Database\Seeders\ClinicStarterConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('seeds an inactive and idempotent starter service catalog and shared resource inventory', function (): void {
    app(ClinicStarterConfigurationSeeder::class)->run();

    expect(Service::query()->count())->toBe(10)
        ->and(Resource::query()->count())->toBe(3)
        ->and(Service::query()->where('is_active', true)->count())->toBe(0)
        ->and(Service::query()->where('is_public_bookable', true)->count())->toBe(0)
        ->and(Resource::query()->where('is_active', true)->count())->toBe(0)
        ->and(Service::query()->whereNull('required_resource_kind')->count())->toBe(0)
        ->and(DB::table('practitioner_services')->count())->toBe(0);

    expect(Resource::query()->where('kind', 'dental_unit')->count())->toBe(2)
        ->and(Resource::query()->where('kind', 'consultation_room')->count())->toBe(1)
        ->and(Service::query()->where('name_fa', 'درمان ریشه (عصب‌کشی)')->value('duration_minutes'))->toBe(90)
        ->and(Service::query()->where('name_fa', 'درمان ریشه (عصب‌کشی)')->value('required_resource_kind'))->toBe('dental_unit');

    app(ClinicStarterConfigurationSeeder::class)->run();

    expect(Service::query()->count())->toBe(10)
        ->and(Resource::query()->count())->toBe(3)
        ->and(Practitioner::query()->count())->toBe(0);
});
