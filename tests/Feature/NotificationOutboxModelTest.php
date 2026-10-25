<?php

use App\NotificationOutbox;

it('uses the planned notification outbox fields and hides private references', function () {
    $outbox = new NotificationOutbox;

    expect($outbox->getFillable())->toBe([
        'channel',
        'recipient_hash',
        'template',
        'payload_ref',
        'status',
        'attempts',
        'created_at',
        'sent_at',
    ])
        ->and($outbox->getHidden())->toContain('recipient_hash', 'payload_ref')
        ->and($outbox->timestamps)->toBeFalse()
        ->and($outbox->getCasts())
        ->toMatchArray([
            'attempts' => 'integer',
            'created_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
        ]);
});

it('makes synthetic outbox data without saving or sending it', function () {
    $outbox = NotificationOutbox::factory()->make();

    expect($outbox->exists)->toBeFalse()
        ->and($outbox->channel)->toBe('sms')
        ->and($outbox->recipient_hash)->toMatch('/^[a-f0-9]{64}$/')
        ->and($outbox->status)->toBe('synthetic-test-only')
        ->and($outbox->attempts)->toBe(0)
        ->and($outbox->sent_at)->toBeNull();
});
