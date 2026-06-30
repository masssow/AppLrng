<?php

declare(strict_types=1);

namespace App\Application\Progression\Service;

use App\Domain\Parcours\Entity\Parcours;
use App\Domain\Parcours\Entity\Ressource;
use App\Domain\Parcours\Enum\StatutEtape;
use App\Domain\Progression\Entity\Progression;
use App\Domain\Progression\Repository\ProgressionRepositoryInterface;

class ProgressionCalculator
{
    public function __construct(
        private readonly ProgressionRepositoryInterface $progressionRepository,
    ) {}

    public function recalculer(Parcours $parcours): void
    {
        $progression  = $parcours->getProgression();
        $ressources   = $parcours->getRessources();
        $total        = count($ressources);
        $consolidees  = 0;
        $fragiles     = [];

        foreach ($ressources as $ressource) {
            if ($ressource->getStatut() === \App\Domain\Parcours\Enum\StatutRessource::CONSOLIDEE) {
                ++$consolidees;
            }
        }

        $scoreConsolidation = $total > 0
            ? (int) round(($consolidees / $total) * 100)
            : 0;

        $projet      = $parcours->getProjetFilRouge();
        $etapes      = $projet !== null ? $projet->getMicroEtapes() : [];
        $totalEtapes = count($etapes);
        $completes   = 0;

        foreach ($etapes as $etape) {
            if ($etape->getStatut() === \App\Domain\Projet\Enum\StatutEtape::COMPLETE) {
                ++$completes;
            }
        }

        $scoreProjet = $totalEtapes > 0
            ? (int) round(($completes / $totalEtapes) * 100)
            : 0;

        $progression->setScoreConsolidation($scoreConsolidation);
        $progression->setScoreProjet($scoreProjet);
        $progression->setRessourcesTotal($total);
        $progression->setRessourcesConsolidees($consolidees);
        $progression->setSujetsFragiles($fragiles);
        $progression->setDerniereActivite(new \DateTime());

        $this->progressionRepository->save($progression, true);
    }

    public function ajouterSujetFragile(Progression $progression, Ressource $ressource): void
    {
        $fragiles = $progression->getSujetsFragiles();

        if (!in_array($ressource->getTitre(), $fragiles, true)) {
            $fragiles[] = $ressource->getTitre();
            $progression->setSujetsFragiles($fragiles);
            $this->progressionRepository->save($progression, true);
        }
    }
}
