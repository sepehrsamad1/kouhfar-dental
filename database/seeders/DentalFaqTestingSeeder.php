<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\ContentItem;
use App\Enums\ContentItemStatus;
use Illuminate\Database\Seeder;
use LogicException;

final class DentalFaqTestingSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('testing')) {
            throw new LogicException('DentalFaqTestingSeeder is available only in the testing environment.');
        }

        $this->call(DentalFaqDraftSeeder::class);

        ContentItem::query()
            ->where('content_key', 'like', 'dental-faq-%')
            ->where('approval_scope', 'clinical')
            ->update([
                'status' => ContentItemStatus::Published->value,
                'published_at' => now('UTC'),
            ]);
    }
}
