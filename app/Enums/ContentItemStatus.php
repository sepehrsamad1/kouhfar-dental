<?php

declare(strict_types=1);

namespace App\Enums;

enum ContentItemStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Published = 'published';
    case Archived = 'archived';
}
