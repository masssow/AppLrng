<?php

declare(strict_types=1);

namespace App\Domain\Parcours\Enum;

enum StatutParcours: string
{
    case BROUILLON = 'brouillon';
    case ACTIF     = 'actif';
    case PAUSE     = 'pause';
    case TERMINE   = 'termine';
    case ABANDONNE = 'abandonne';
}
