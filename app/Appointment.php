<?php

declare(strict_types=1);

namespace App;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'patient_id',
        'service_id',
        'status',
        'start_at',
        'end_at',
        'source',
        'expires_at',
        'created_by',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'status' => AppointmentStatus::class,
            'start_at' => 'immutable_datetime',
            'end_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'version' => 'integer',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AppointmentEvent::class);
    }
}
