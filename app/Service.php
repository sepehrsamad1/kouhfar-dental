<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name_fa',
        'duration_minutes',
        'buffer_before_minutes',
        'buffer_after_minutes',
        'is_active',
        'is_public_bookable',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'buffer_before_minutes' => 'integer',
            'buffer_after_minutes' => 'integer',
            'is_active' => 'boolean',
            'is_public_bookable' => 'boolean',
            'version' => 'integer',
        ];
    }

    public function practitioners(): BelongsToMany
    {
        return $this->belongsToMany(Practitioner::class, 'practitioner_services');
    }

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'service_resources')
            ->withPivot('quantity');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
