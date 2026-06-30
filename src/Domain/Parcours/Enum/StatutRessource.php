<?php

declare(strict_types=1);

namespace App\Domain\Parcours\Enum;

enum StatutRessource: string
{
    case A_FAIRE    = 'a_faire';
    case EN_COURS   = 'en_cours';
    case VUE        = 'vue';
    case CONSOLIDEE = 'consolidee';
}
