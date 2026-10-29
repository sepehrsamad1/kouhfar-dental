<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $mobile_normalized
 */
class Patient extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'full_name',
        'mobile_normalized',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'immutable_datetime',
            'version' => 'integer',
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function consentRecords(): HasMany
    {
        return $this->hasMany(ConsentRecord::class);
    }

    public function mediaAssets(): HasMany
    {
        return $this->hasMany(MediaAsset::class, 'owner_patient_id');
    }
}
