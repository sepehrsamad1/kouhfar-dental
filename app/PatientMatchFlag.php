<?php

declare(strict_types=1);

namespace App;

use App\Enums\PatientMatchFlagStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMatchFlag extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'patient_a_id',
        'patient_b_id',
        'reason',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => PatientMatchFlagStatus::class,
        ];
    }

    public function patientA(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_a_id');
    }

    public function patientB(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_b_id');
    }
}
