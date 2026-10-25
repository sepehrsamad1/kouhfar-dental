<?php

use App\DeletionRequest;

it('uses the planned deletion request fields without enabling deletion', function () {
    $request = new DeletionRequest;

    expect($request->getFillable())->toBe([
        'target_type',
        'target_id',
        'requested_by',
        'approved_by',
        'status',
        'created_at',
    ])
        ->and($request->timestamps)->toBeFalse()
        ->and($request->getCasts())
        ->toMatchArray([
            'created_at' => 'immutable_datetime',
        ]);
});

it('makes synthetic pending deletion request data without saving it', function () {
    $request = DeletionRequest::factory()->make();

    expect($request->exists)->toBeFalse()
        ->and($request->target_type)->toBe('Patient')
        ->and($request->status)->toBe('pending')
        ->and($request->approved_by)->toBeNull();
});
