<?php

declare(strict_types=1);

namespace App\Domain\Consolidation\Event;

use App\Domain\Consolidation\Entity\SessionConsolidation;

/** Dispatché quand questions + exercice sont générés (statut PRET) — ne signifie pas que la ressource est consolidée */
final class ConsolidationPreteEvent
{
    public function __construct(
        public readonly SessionConsolidation $session,
    ) {}
}
