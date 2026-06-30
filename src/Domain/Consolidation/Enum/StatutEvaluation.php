<?php

declare(strict_types=1);

namespace App\Domain\Consolidation\Enum;

enum StatutEvaluation: string
{
    case EN_ATTENTE = 'en_attente';
    case EVALUATION = 'evaluation';
    case EVALUEE    = 'evaluee';
    case ERREUR     = 'erreur';
}
