<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationOutbox extends Model
{
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $hidden = [
        'recipient_hash',
        'payload_ref',
    ];

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
        ];
    }
}
