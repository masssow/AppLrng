<?php

declare(strict_types=1);

namespace App\Domain\Parcours\Event;

use App\Domain\Parcours\Entity\Parcours;

/** Dispatché quand toutes les MicroEtapes sont COMPLETE */
final class ProjetTermineEvent
{
    public function __construct(
        public readonly Parcours $parcours,
    ) {}
}
