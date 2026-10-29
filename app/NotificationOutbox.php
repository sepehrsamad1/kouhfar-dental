<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Enums\AppointmentNotificationType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $appointment_id
 * @property AppointmentNotificationType $type
 * @property string $dedupe_key
 * @property CarbonImmutable $scheduled_at
 * @property CarbonImmutable|null $sent_at
 * @property int $attempts
 * @property string|null $last_error
 */
class NotificationOutbox extends Model
{
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $hidden = [
        'recipient_hash',
        'payload_ref',
    ];

    public static function enqueue(string $appointmentId, AppointmentNotificationType $type, CarbonImmutable $scheduledAt, string $dedupeKey): self
    {
        self::query()->insertOrIgnore([
            'id' => (string) str()->uuid(),
            'appointment_id' => $appointmentId,
            'type' => $type->value,
            'dedupe_key' => $dedupeKey,
            'scheduled_at' => $scheduledAt->utc(),
            'attempts' => 0,
        ]);

        return self::query()->where('dedupe_key', $dedupeKey)->firstOrFail();
    }

    protected $fillable = [
        'channel',
        'recipient_hash',
        'template',
        'payload_ref',
        'status',
        'attempts',
        'created_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'created_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
            'scheduled_at' => 'immutable_datetime',
            'type' => AppointmentNotificationType::class,
        ];
    }
}
