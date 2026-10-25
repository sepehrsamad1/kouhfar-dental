<?php

declare(strict_types=1);

namespace App;

use App\Enums\ContentItemStatus;
use App\Enums\ContentKind;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentItem extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'kind',
        'locale',
        'body',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'kind' => ContentKind::class,
            'body' => 'array',
            'status' => ContentItemStatus::class,
            'published_at' => 'immutable_datetime',
        ];
    }
}
