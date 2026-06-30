<?php

declare(strict_types=1);

namespace App\Application\Dashboard\Service;

use App\Application\Dashboard\DTO\ReprendreDTO;
use App\Application\Dashboard\DTO\ScoreAgregeDTO;
use App\Domain\Consolidation\Repository\SessionConsolidationRepositoryInterface;
use App\Domain\Parcours\Enum\StatutRessource;
use App\Domain\Parcours\Repository\ParcoursRepositoryInterface;
use App\Domain\Projet\Repository\MicroEtapeRepositoryInterface;
use App\Domain\Revision\Entity\RevisionSpacee;
use App\Domain\Revision\Repository\RevisionSpaceeRepositoryInterface;
use App\Domain\Shared\Entity\User;

class DashboardService
{
    public function __construct(
        private readonly ParcoursRepositoryInterface             $parcoursRepository,
        private readonly SessionConsolidationRepositoryInterface $sessionRepository,
        private readonly MicroEtapeRepositoryInterface           $microEtapeRepository,
        private readonly RevisionSpaceeRepositoryInterface       $revisionRepository,
    ) {}

    public function getActionAReprendre(User $user): ?ReprendreDTO
    {
        // 1. Session PRET avec question ou exercice sans réponse
        $session = $this->sessionRepository->findPretPourUser($user);
        if ($session !== null) {
            $hasAction = false;
            foreach ($session->getQuestions() as $question) {
                if ($question->getReponseUtilisateur() === null) {
                    $hasAction = true;
                    break;
                }
            }
            if (!$hasAction && $session->getExercice() !== null && $session->getExercice()->getRenduUtilisateur() === null) {
                $hasAction = true;
            }
            if ($hasAction) {
                return new ReprendreDTO(
                    type: 'consolidation',
                    titre: $session->getRessource()->getTitre(),
                    route: 'app_consolidation_show',
                    routeParams: ['id' => (string) $session->getId()],
                    contexte: $session->getRessource()->getParcours()->getTitre(),
                );
            }
        }

        // 2. MicroEtape EN_COURS
        $etape = $this->microEtapeRepository->findEnCoursPourUser($user);
        if ($etape !== null) {
            return new ReprendreDTO(
                type: 'micro_etape',
                titre: $etape->getTitre(),
                route: 'app_projet_etape_rendu',
                routeParams: ['id' => (string) $etape->getId()],
                contexte: $etape->getProjet()->getParcours()->getTitre(),
            );
        }

        // 3. Ressource EN_COURS ou VUE sans session active
        $parcoursList = $this->parcoursRepository->findByUserOrderedByDate($user);
        foreach ($parcoursList as $parcours) {
            foreach ($parcours->getRessources() as $ressource) {
                $statut = $ressource->getStatut();
                if ($statut === StatutRessource::EN_COURS || $statut === StatutRessource::VUE) {
                    return new ReprendreDTO(
                        type: 'ressource',
                        titre: $ressource->getTitre(),
                        route: 'app_ressource_show',
                        routeParams: ['id' => (string) $ressource->getId()],
                        contexte: $parcours->getTitre(),
                    );
                }
            }
        }

        return null;
    }

    /** @return RevisionSpacee[] */
    public function getRevisionsDuJour(User $user): array
    {
        return $this->revisionRepository->findPendingForUser($user, new \DateTime());
    }

    public function getStreak(User $user): int
    {
        $depuis = new \DateTime('-60 days');

        $dates = array_unique(array_merge(
            $this->sessionRepository->findDatesCompletionPourUser($user, $depuis),
            $this->revisionRepository->findDatesCompletionPourUser($user, $depuis),
            $this->microEtapeRepository->findDatesCompletionPourUser($user, $depuis),
        ));

        if (empty($dates)) {
            return 0;
        }

        $datesMap = array_flip($dates);

        $today     = (new \DateTime())->format('Y-m-d');
        $yesterday = (new \DateTime('-1 day'))->format('Y-m-d');

        // Tolérance d'un jour : ne pas casser le streak si la journée n'est pas encore finie
        if (isset($datesMap[$today])) {
            $cursor = new \DateTime($today);
        } elseif (isset($datesMap[$yesterday])) {
            $cursor = new \DateTime($yesterday);
        } else {
            return 0;
        }

        $streak = 0;
        while (isset($datesMap[$cursor->format('Y-m-d')])) {
            ++$streak;
            $cursor->modify('-1 day');
        }

        return $streak;
    }

    public function getScoreAgrege(User $user): ?ScoreAgregeDTO
    {
        $parcoursList = $this->parcoursRepository->findActiveForUser($user);

        if (empty($parcoursList)) {
            return null;
        }

        $totalPoids = 0;
        $totalScore = 0;

        foreach ($parcoursList as $parcours) {
            $progression = $parcours->getProgression();
            if ($progression !== null) {
                $poids       = max(1, $progression->getRessourcesTotal());
                $totalPoids += $poids;
                $totalScore += $progression->getScoreConsolidation() * $poids;
            }
        }

        $valeur = $totalPoids > 0 ? (int) round($totalScore / $totalPoids) : 0;

        $libelle = match (true) {
            $valeur >= 80 => 'Excellente maîtrise',
            $valeur >= 60 => 'Bonne progression',
            $valeur >= 35 => 'En cours de construction',
            default       => 'Tout début de parcours',
        };

        return new ScoreAgregeDTO(valeur: $valeur, libelleQualitatif: $libelle);
    }

    /** @return \App\Domain\Parcours\Entity\Parcours[] */
    public function getParcoursActifs(User $user): array
    {
        return $this->parcoursRepository->findByUserOrderedByDate($user);
    }
}
