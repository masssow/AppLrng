<?php

declare(strict_types=1);

namespace App\Domain\Parcours\Event;

use App\Domain\Parcours\Entity\Parcours;

/** Dispatché quand le parcours passe BROUILLON → ACTIF après génération IA réussie */
final class ParcoursStructureEvent
{
    public function __construct(
        public readonly Parcours $parcours,
    ) {}
}
