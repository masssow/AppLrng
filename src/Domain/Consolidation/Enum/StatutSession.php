<?php

declare(strict_types=1);

namespace App\Domain\Consolidation\Enum;

enum StatutSession: string
{
    case EN_ATTENTE = 'en_attente';
    case GENERATION = 'generation';
    case PRET       = 'pret';
    case COMPLETE   = 'complete';
    case ERREUR     = 'erreur';
}
