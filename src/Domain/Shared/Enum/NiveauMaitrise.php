<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enum;

enum NiveauMaitrise: string
{
    case DEBUTANT      = 'debutant';
    case INTERMEDIAIRE = 'intermediaire';
    case AVANCE        = 'avance';
}
