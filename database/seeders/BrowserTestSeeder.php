<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\ContentItem;
use App\Enums\ContentItemStatus;
use App\Enums\ContentKind;
use App\Practitioner;
use App\StaffUser;
use Illuminate\Database\Seeder;
use LogicException;

final class BrowserTestSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('testing')) {
            throw new LogicException('Browser test fixtures may only be seeded in the testing environment.');
        }

        app(ClinicStarterConfigurationSeeder::class)->run();

        Practitioner::query()->firstOrCreate(
            ['display_name_fa' => 'پزشک آزمایشی مرورگر'],
            ['is_active' => false]
        );

        ContentItem::query()->updateOrCreate(['content_key' => 'browser-public-content', 'locale' => 'fa', 'version' => 1], ['kind' => ContentKind::Faq, 'body' => ['title' => 'محتوای مرورگر', 'text' => 'محتوای منتشرشده برای تست مرورگر.'], 'status' => ContentItemStatus::Published, 'approval_scope' => 'general', 'published_at' => now('UTC')]);

        $staff = StaffUser::query()->firstOrNew(['email' => 'browser-admin@example.invalid']);
        $staff->forceFill([
            'display_name' => 'Browser Test Admin',
            'role' => 'admin',
            'is_active' => true,
            'password_hash' => 'BrowserOnlyPassword!2026',
        ])->save();
    }
}
