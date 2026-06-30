<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enum;

enum ModeAccompagnement: string
{
    case SOCRATIQUE = 'socratique';
    case MIXTE      = 'mixte';
    case EXPLICATIF = 'explicatif';
}
