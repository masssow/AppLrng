<?php

declare(strict_types=1);

namespace App\Application\IA\Enum;

enum PromptType: string
{
    case PARCOURS      = 'parcours';
    case CONSOLIDATION = 'consolidation';
    case EVALUATION    = 'evaluation';
    case GUIDAGE       = 'guidage';
}
