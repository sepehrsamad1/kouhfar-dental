<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicWorkingHour extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = ['weekday', 'start_local', 'end_local', 'timezone', 'version'];

    protected function casts(): array
    {
        return ['weekday' => 'integer', 'version' => 'integer'];
    }
}
