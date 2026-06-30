<?php

declare(strict_types=1);

namespace App\Domain\Projet\Enum;

enum TypeMicroEtape: string
{
    case LECTURE       = 'lecture';
    case EXERCICE      = 'exercice';
    case OUTIL_EXTERNE = 'outil_externe';
    case LIVRABLE      = 'livrable';
}
