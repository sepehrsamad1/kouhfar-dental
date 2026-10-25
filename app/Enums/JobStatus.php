<?php

declare(strict_types=1);

namespace App\Enums;

enum JobStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Done = 'done';
    case Failed = 'failed';
    case Dead = 'dead';
}
