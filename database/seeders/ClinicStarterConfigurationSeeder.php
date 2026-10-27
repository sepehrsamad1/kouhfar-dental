<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Resource;
use App\Service;
use Illuminate\Database\Seeder;

final class ClinicStarterConfigurationSeeder extends Seeder
{
    /**
     * Starter values are researched planning defaults, not clinical instructions.
     * Records are inactive and not public-bookable until a clinic administrator reviews them.
     * This seeder is explicit and idempotent; it is not called by DatabaseSeeder.
     */
    public function run(): void
    {
        $resources = [
            '10000000-0000-4000-8000-000000000001' => [
                'name_fa' => 'یونیت دندان‌پزشکی ۱',
                'kind' => 'dental_unit',
            ],
            '10000000-0000-4000-8000-000000000002' => [
                'name_fa' => 'یونیت دندان‌پزشکی ۲',
                'kind' => 'dental_unit',
            ],
            '10000000-0000-4000-8000-000000000003' => [
                'name_fa' => 'فضای مشاوره',
                'kind' => 'consultation_room',
            ],
        ];

        foreach ($resources as $id => $attributes) {
            Resource::query()->firstOrCreate(['id' => $id], $attributes + ['is_active' => false]);
        }

        $services = [
            '20000000-0000-4000-8000-000000000001' => ['معاینه دوره‌ای', 20, 0, 5, 'dental_unit'],
            '20000000-0000-4000-8000-000000000002' => ['مشاوره اولیه و طرح درمان', 30, 0, 5, 'consultation_room'],
            '20000000-0000-4000-8000-000000000003' => ['جرم‌گیری و پاک‌سازی دندان', 40, 0, 10, 'dental_unit'],
            '20000000-0000-4000-8000-000000000004' => ['ترمیم دندان (پرکردگی)', 45, 0, 10, 'dental_unit'],
            '20000000-0000-4000-8000-000000000005' => ['درمان ریشه (عصب‌کشی)', 90, 0, 15, 'dental_unit'],
            '20000000-0000-4000-8000-000000000006' => ['کشیدن ساده دندان', 30, 0, 10, 'dental_unit'],
            '20000000-0000-4000-8000-000000000007' => ['کشیدن جراحی دندان', 60, 0, 15, 'dental_unit'],
            '20000000-0000-4000-8000-000000000008' => ['آماده‌سازی روکش', 75, 0, 10, 'dental_unit'],
            '20000000-0000-4000-8000-000000000009' => ['تحویل و تنظیم روکش', 45, 0, 10, 'dental_unit'],
            '20000000-0000-4000-8000-000000000010' => ['مشاوره زیبایی دندان', 30, 0, 5, 'consultation_room'],
        ];

        foreach ($services as $id => [$name, $duration, $before, $after, $resourceKind]) {
            Service::query()->firstOrCreate(['id' => $id], [
                'name_fa' => $name,
                'duration_minutes' => $duration,
                'buffer_before_minutes' => $before,
                'buffer_after_minutes' => $after,
                'required_resource_kind' => $resourceKind,
                'is_active' => false,
                'is_public_bookable' => false,
            ]);
        }
    }
}
