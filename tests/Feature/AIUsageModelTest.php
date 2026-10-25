<?php

use App\AIUsage;

it('uses the explicit AI usage table name', function () {
    expect((new AIUsage)->getTable())->toBe('ai_usage');
});

it('disables automatic timestamps and casts usage fields', function () {
    $usage = new AIUsage;

    expect($usage->usesTimestamps())->toBeFalse()
        ->and($usage->getFillable())->toBe([
            'day',
            'feature',
            'input_tokens',
            'output_tokens',
            'cost_estimate',
            'request_count',
        ])
        ->and($usage->getCasts())
        ->toMatchArray([
            'day' => 'immutable_date',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'request_count' => 'integer',
        ]);
});

it('makes synthetic AI usage counters without saving them', function () {
    $usage = AIUsage::factory()->make();

    expect($usage->exists)->toBeFalse()
        ->and($usage->feature)->toBe('synthetic-test-only')
        ->and($usage->input_tokens)->toBe(0)
        ->and($usage->output_tokens)->toBe(0)
        ->and($usage->request_count)->toBe(0);
});
