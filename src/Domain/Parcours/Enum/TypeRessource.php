<?php

declare(strict_types=1);

namespace App\Domain\Parcours\Enum;

enum TypeRessource: string
{
    case VIDEO   = 'video';
    case ARTICLE = 'article';
    case COURS   = 'cours';
    case PODCAST = 'podcast';
    case LIVRE   = 'livre';
}
