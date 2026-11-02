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
            'provider',
            'model',
            'capability',
            'input_tokens',
            'output_tokens',
            'cost_estimate',
            'estimated_cost_irr',
            'actual_cost_irr',
            'request_count',
        ])
        ->and($usage->getCasts())
        ->toMatchArray([
            'day' => 'immutable_date',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'request_count' => 'integer',
            'estimated_cost_irr' => 'decimal:2',
            'actual_cost_irr' => 'decimal:2',
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
