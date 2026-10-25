<?php

use Illuminate\Support\Facades\DB;

it('can connect to PostgreSQL in integration mode', function () {
    if (config('database.default') !== 'pgsql') {
        $this->markTestSkipped(
            'PostgreSQL integration test runs only with DB_CONNECTION=pgsql.'
        );
    }

    $result = DB::selectOne('select 1 as ok');

    expect((int) $result->ok)->toBe(1);
});
