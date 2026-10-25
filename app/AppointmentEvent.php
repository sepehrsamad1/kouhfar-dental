<?php

declare(strict_types=1);

namespace App;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentEvent extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'appointment_id',
        'actor_type',
        'actor_id',
        'from_status',
        'to_status',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => AppointmentStatus::class,
            'to_status' => AppointmentStatus::class,
            'occurred_at' => 'immutable_datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
