<?php

declare(strict_types=1);

namespace App\Application\Parcours\Service;

use App\Application\IA\DTO\ParcoursStructureDTO;
use App\Application\Security\OwnershipChecker;
use App\Domain\Parcours\Entity\Parcours;
use App\Domain\Parcours\Entity\Ressource;
use App\Domain\Parcours\Enum\StatutParcours;
use App\Domain\Parcours\Event\ParcoursStructureEvent;
use App\Domain\Parcours\Repository\ParcoursRepositoryInterface;
use App\Domain\Parcours\Repository\RessourceRepositoryInterface;
use App\Domain\Progression\Repository\ProgressionRepositoryInterface;
use App\Application\IA\PromptVersions;
use App\Domain\Projet\Entity\MicroEtape;
use App\Domain\Projet\Entity\ProjetFilRouge;
use App\Domain\Projet\Enum\TypeMicroEtape;
use App\Domain\Projet\Repository\MicroEtapeRepositoryInterface;
use App\Domain\Projet\Repository\ProjetFilRougeRepositoryInterface;
use App\Domain\Shared\Entity\Domaine;
use App\Domain\Shared\Entity\User;
use App\Domain\Shared\Enum\NiveauMaitrise;
use App\Application\Messenger\Message\GenerationParcoursMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ParcoursService
{
    public function __construct(
        private readonly ParcoursRepositoryInterface    $parcoursRepository,
        private readonly RessourceRepositoryInterface   $ressourceRepository,
        private readonly ProgressionRepositoryInterface $progressionRepository,
        private readonly ProjetFilRougeRepositoryInterface $projetRepository,
        private readonly MicroEtapeRepositoryInterface     $microEtapeRepository,
        private readonly MessageBusInterface            $bus,
        private readonly EventDispatcherInterface       $eventDispatcher,
        private readonly OwnershipChecker               $ownershipChecker,
    ) {}

    /**
     * @param array<int, array{titre: string, type: \App\Domain\Parcours\Enum\TypeRessource, url?: string|null, source?: string|null, description?: string|null, dureeEstimeeMinutes?: int|null}> $ressourcesData
     */
    public function initier(
        User $user,
        string $titre,
        string $objectif,
        Domaine $domaine,
        NiveauMaitrise $niveau,
        int $dureeCibleSemaines,
        array $ressourcesData,
    ): Parcours {
        $parcours = new Parcours($user, $domaine, $titre, $objectif, $niveau, $dureeCibleSemaines);
        $this->parcoursRepository->save($parcours);

        foreach ($ressourcesData as $i => $data) {
            $ressource = new Ressource(
                parcours: $parcours,
                titre: $data['titre'],
                type: $data['type'],
                ordre: $i + 1,
                url: $data['url'] ?? null,
                source: $data['source'] ?? null,
                description: $data['description'] ?? null,
                dureeEstimeeMinutes: $data['dureeEstimeeMinutes'] ?? null,
            );
            $this->ressourceRepository->save($ressource);
        }

        $progression = new \App\Domain\Progression\Entity\Progression($parcours);
        $this->progressionRepository->save($progression);

        $this->parcoursRepository->save($parcours, true);

        return $parcours;
    }

    public function lancerStructuration(Parcours $parcours): void
    {
        if ($parcours->getRessources()->count() === 0) {
            throw new \LogicException('Impossible de structurer un parcours sans ressources.');
        }

        $this->bus->dispatch(new GenerationParcoursMessage((string) $parcours->getId()));
    }

    public function passerActif(Parcours $parcours): void
    {
        $parcours->setStatut(StatutParcours::ACTIF);
        $this->parcoursRepository->save($parcours, true);

        $this->eventDispatcher->dispatch(new ParcoursStructureEvent($parcours));
    }

    public function supprimer(Parcours $parcours, User $user): void
    {
        $this->ownershipChecker->assertParcoursBelongsToUser($parcours, $user);

        $this->parcoursRepository->remove($parcours, true);
    }

    public function appliquerStructureIA(Parcours $parcours, ParcoursStructureDTO $dto): void
    {
        // Réordonner les ressources selon l'IA
        $ressources     = $parcours->getRessources()->toArray();
        $indexByOrdre   = [];
        foreach ($ressources as $r) {
            $indexByOrdre['r' . $r->getOrdre()] = $r;
        }

        $nouvelOrdre = 1;
        foreach ($dto->ordreSuggere as $ref) {
            if (isset($indexByOrdre[$ref])) {
                $indexByOrdre[$ref]->setOrdre($nouvelOrdre++);
                $this->ressourceRepository->save($indexByOrdre[$ref]);
            }
        }

        // Créer le projet fil rouge si demandé
        $projetData = $dto->projetFilRouge;
        if (!empty($projetData['titre'])) {
            $projet = new ProjetFilRouge(
                $parcours,
                $projetData['titre'],
                $projetData['description'] ?? '',
                PromptVersions::PARCOURS_V1,
            );
            $this->projetRepository->save($projet);

            $ordre = 1;
            foreach ($projetData['micro_etapes'] ?? [] as $etapeData) {
                $type  = TypeMicroEtape::tryFrom(strtoupper($etapeData['type'] ?? '')) ?? TypeMicroEtape::LIVRABLE;
                $etape = new MicroEtape(
                    $projet,
                    $etapeData['titre'] ?? 'Étape ' . $ordre,
                    $etapeData['description'] ?? '',
                    $type,
                    $ordre,
                );
                $this->microEtapeRepository->save($etape);
                $ordre++;
            }

            $this->projetRepository->save($projet, true);
        }

        $this->parcoursRepository->save($parcours, true);
    }
}
