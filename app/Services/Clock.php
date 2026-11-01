<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;

interface Clock
{
    public function now(): CarbonImmutable;
}
