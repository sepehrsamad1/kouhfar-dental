<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleBreak extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = ['practitioner_id', 'weekday', 'start_local', 'end_local', 'timezone', 'version'];

    protected function casts(): array
    {
        return ['weekday' => 'integer', 'version' => 'integer'];
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }
}
