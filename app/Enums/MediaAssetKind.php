<?php

declare(strict_types=1);

namespace App\Enums;

enum MediaAssetKind: string
{
    case CaseBefore = 'case_before';
    case CaseAfter = 'case_after';
    case SmileInput = 'smile_input';
    case Other = 'other';
}
