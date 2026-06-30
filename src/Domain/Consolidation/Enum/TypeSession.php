<?php

declare(strict_types=1);

namespace App\Domain\Consolidation\Enum;

enum TypeSession: string
{
    case INITIALE     = 'initiale';
    case REVISION     = 'revision';
    case REGENERATION = 'regeneration';
}
