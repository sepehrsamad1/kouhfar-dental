<?php

declare(strict_types=1);

namespace App\Enums;

enum ContentKind: string
{
    case Service = 'service';
    case Faq = 'faq';
    case Case = 'case';
    case Doctor = 'doctor';
    case ClinicInfo = 'clinic_info';
}
