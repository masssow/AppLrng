<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enum;

enum PlanUtilisateur: string
{
    case GRATUIT = 'gratuit';
    case PREMIUM = 'premium';
}
