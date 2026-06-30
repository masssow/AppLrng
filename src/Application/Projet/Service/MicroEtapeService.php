<?php

declare(strict_types=1);

namespace App\Application\Projet\Service;

use App\Application\IA\AIOrchestrator;
use App\Application\Security\OwnershipChecker;
use App\Domain\Consolidation\Repository\SessionConsolidationRepositoryInterface;
use App\Domain\Parcours\Repository\RessourceRepositoryInterface;
use App\Domain\Parcours\Repository\ParcoursRepositoryInterface;
use App\Domain\Projet\Entity\MicroEtape;
use App\Domain\Projet\Enum\StatutEtape;
use App\Domain\Projet\Enum\StatutProjet;
use App\Domain\Projet\Event\ProjetTermineEvent;
use App\Domain\Projet\Repository\MicroEtapeRepositoryInterface;
use App\Domain\Shared\Entity\User;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MicroEtapeService
{
    public function __construct(
        private readonly MicroEtapeRepositoryInterface           $microEtapeRepository,
        private readonly SessionConsolidationRepositoryInterface $sessionRepository,
        private readonly RessourceRepositoryInterface            $ressourceRepository,
        private readonly ParcoursRepositoryInterface             $parcoursRepository,
        private readonly AIOrchestrator                          $aiOrchestrator,
        private readonly EventDispatcherInterface                $eventDispatcher,
        private readonly OwnershipChecker                        $ownershipChecker,
    ) {}

    public function demarrer(MicroEtape $etape, User $user): void
    {
        $this->ownershipChecker->assertMicroEtapeAppartientA($etape, $user);

        $etape->setStatut(StatutEtape::EN_COURS);
        $etape->setDebloqueeAt(new \DateTimeImmutable());
        $this->microEtapeRepository->save($etape, true);
    }

    public function soumettreRendu(MicroEtape $etape, string $rendu, User $user): void
    {
        $this->ownershipChecker->assertMicroEtapeAppartientA($etape, $user);

        $etape->setStatut(StatutEtape::COMPLETE);
        $etape->setCompletedAt(new \DateTimeImmutable());
        $etape->setRendu($rendu);
        $this->microEtapeRepository->save($etape, true);

        // Débloquer la suivante
        $suivante = $this->microEtapeRepository->findSuivante($etape);
        if ($suivante !== null) {
            $suivante->setStatut(StatutEtape::DISPONIBLE);
            $suivante->setDebloqueeAt(new \DateTimeImmutable());
            $this->microEtapeRepository->save($suivante, true);
        } else {
            // Dernière étape → projet terminé
            $projet = $etape->getProjet();
            $projet->setStatut(StatutProjet::TERMINE);
            $this->eventDispatcher->dispatch(new ProjetTermineEvent($projet->getParcours()));
        }
    }

    public function demanderPiste(MicroEtape $etape, string $blocage, User $user): string
    {
        $this->ownershipChecker->assertMicroEtapeAppartientA($etape, $user);

        $parcours           = $etape->getProjet()->getParcours();
        $modeAccompagnement = $parcours->getModeAccompagnement()->value;
        $contexteProjet     = $etape->getProjet()->getDescription();

        $ressourcesConsolidees = $this->ressourceRepository->findByParcoursForUser($parcours, $parcours->getUser());
        $historique = array_map(fn($r) => [
            'titre'     => $r->getTitre(),
            'concepts'  => '',
            'pomodoros' => $r->getPomodorosSuggeres(),
        ], $ressourcesConsolidees);

        return $this->aiOrchestrator->guiderMicroEtape($etape, $blocage, $contexteProjet, $historique, $modeAccompagnement);
    }
}
