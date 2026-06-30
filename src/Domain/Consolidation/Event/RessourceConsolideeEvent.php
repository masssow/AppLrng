<?php

declare(strict_types=1);

namespace App\Domain\Consolidation\Event;

use App\Domain\Consolidation\Entity\SessionConsolidation;
use App\Domain\Parcours\Entity\Ressource;

/** Dispatché quand l'utilisateur termine la session ET score >= 3/5 — déclenche ProgressionSubscriber + RevisionSubscriber */
final class RessourceConsolideeEvent
{
    public function __construct(
        public readonly SessionConsolidation $session,
        public readonly Ressource $ressource,
    ) {}
}
