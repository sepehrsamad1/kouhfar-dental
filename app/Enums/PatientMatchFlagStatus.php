<?php

declare(strict_types=1);

namespace App\Enums;

enum PatientMatchFlagStatus: string
{
    case Open = 'open';
    case Dismissed = 'dismissed';
    case Merged = 'merged';
}
