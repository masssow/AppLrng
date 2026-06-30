<?php

declare(strict_types=1);

namespace App\Domain\Projet\Enum;

enum StatutProjet: string
{
    case NON_DEMARRE = 'non_demarre';
    case EN_COURS    = 'en_cours';
    case TERMINE     = 'termine';
}
