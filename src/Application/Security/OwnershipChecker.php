<?php

declare(strict_types=1);

namespace App\Application\Security;

use App\Domain\Consolidation\Entity\SessionConsolidation;
use App\Domain\Parcours\Entity\Parcours;
use App\Domain\Parcours\Entity\Ressource;
use App\Domain\Projet\Entity\MicroEtape;
use App\Domain\Shared\Entity\User;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class OwnershipChecker
{
    public function assertParcoursBelongsToUser(Parcours $parcours, User $user): void
    {
        if ($parcours->getUser() !== $user) {
            throw new AccessDeniedException('Ce parcours n\'appartient pas à l\'utilisateur.');
        }
    }

    public function assertRessourceBelongsToUser(Ressource $ressource, User $user): void
    {
        if ($ressource->getParcours()->getUser() !== $user) {
            throw new AccessDeniedException('Cette ressource n\'appartient pas à l\'utilisateur.');
        }
    }

    public function assertSessionBelongsToUser(SessionConsolidation $session, User $user): void
    {
        if ($session->getRessource()->getParcours()->getUser() !== $user) {
            throw new AccessDeniedException('Cette session n\'appartient pas à l\'utilisateur.');
        }
    }

    public function assertMicroEtapeAppartientA(MicroEtape $etape, User $user): void
    {
        if ($etape->getProjet()->getParcours()->getUser() !== $user) {
            throw new AccessDeniedException('Cette micro-étape n\'appartient pas à l\'utilisateur.');
        }
    }
}
