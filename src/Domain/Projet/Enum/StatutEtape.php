<?php

declare(strict_types=1);

namespace App\Domain\Projet\Enum;

enum StatutEtape: string
{
    case VERROUILLEE = 'verrouillee';
    case DISPONIBLE  = 'disponible';
    case EN_COURS    = 'en_cours';
    case COMPLETE    = 'complete';
}
