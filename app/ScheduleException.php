<?php

declare(strict_types=1);

namespace App;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleException extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'practitioner_id',
        'starts_at',
        'ends_at',
        'kind',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'version' => 'integer',
        ];
    }

    public function setStartsAtAttribute(mixed $value): void
    {
        $this->attributes['starts_at'] = $this->normalizeDateTimeForDatabase($value);
    }

    public function setEndsAtAttribute(mixed $value): void
    {
        $this->attributes['ends_at'] = $this->normalizeDateTimeForDatabase($value);
    }

    private function normalizeDateTimeForDatabase(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $dateTime = $value instanceof DateTimeInterface
            ? CarbonImmutable::instance($value)
            : CarbonImmutable::parse((string) $value, config('app.timezone'));

        return $dateTime->utc()->format($this->getDateFormat());
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }
}
