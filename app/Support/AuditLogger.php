<?php

declare(strict_types=1);

namespace App\Support;

use App\AuditLog;
use App\StaffUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class AuditLogger
{
    private const ALLOWED_META_KEYS = [
        'status',
        'role',
        'is_active',
        'service_id',
        'practitioner_id',
        'resource_id',
        'appointment_id',
        'content_id',
        'media_asset_id',
        'reason_code',
    ];

    public static function record(
        StaffUser $actor,
        string $action,
        ?Model $target = null,
        ?array $before = null,
        ?array $after = null,
    ): AuditLog {
        $meta = [];

        if ($before !== null) {
            $meta['before'] = self::filterMeta($before);
        }

        if ($after !== null) {
            $meta['after'] = self::filterMeta($after);
        }

        return AuditLog::query()->create([
            'actor_id' => $actor->getKey(),
            'action' => $action,
            'target_type' => $target === null ? null : Str::afterLast($target::class, '\\'),
            'target_id' => $target?->getKey(),
            'occurred_at' => now('UTC'),
            'request_id' => request()?->attributes->get('request_id'),
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    private static function filterMeta(array $values): array
    {
        return collect($values)
            ->only(self::ALLOWED_META_KEYS)
            ->filter(fn ($value): bool => is_scalar($value) || $value === null)
            ->map(fn ($value): string|int|float|bool|null => $value)
            ->all();
    }
}
