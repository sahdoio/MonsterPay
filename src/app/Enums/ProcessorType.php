<?php

declare(strict_types=1);

namespace App\Enums;

enum ProcessorType: string
{
    case DEFAULT = 'default';
    case FALLBACK = 'fallback';
}
