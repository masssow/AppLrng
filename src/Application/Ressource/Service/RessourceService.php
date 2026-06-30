<?php

declare(strict_types=1);

namespace App\Application\Ressource\Service;

use App\Application\Security\OwnershipChecker;
use App\Domain\Parcours\Entity\Ressource;
use App\Domain\Parcours\Enum\StatutRessource;
use App\Domain\Parcours\Enum\TypeRessource;
use App\Domain\Parcours\Repository\RessourceRepositoryInterface;
use App\Domain\Shared\Entity\User;

class RessourceService
{
    public function __construct(
        private readonly RessourceRepositoryInterface $ressourceRepository,
        private readonly OwnershipChecker             $ownershipChecker,
    ) {}

    public function passerEnCours(Ressource $ressource, User $user): void
    {
        $this->ownershipChecker->assertRessourceBelongsToUser($ressource, $user);

        if ($ressource->getStatut() === StatutRessource::A_FAIRE) {
            $ressource->setStatut(StatutRessource::EN_COURS);
            $this->ressourceRepository->save($ressource, true);
        }
    }

    public function passerVue(Ressource $ressource, User $user): void
    {
        $this->ownershipChecker->assertRessourceBelongsToUser($ressource, $user);

        $ressource->setStatut(StatutRessource::VUE);
        $ressource->setViewedAt(new \DateTime());
        $this->ressourceRepository->save($ressource, true);
    }

    public function passerConsolidee(Ressource $ressource): void
    {
        $ressource->setStatut(StatutRessource::CONSOLIDEE);
        $ressource->setConsolidatedAt(new \DateTime());
        $this->ressourceRepository->save($ressource, true);
    }

    public function calculerPomodorosSuggeres(?int $dureeMinutes, TypeRessource $type): int
    {
        if ($dureeMinutes !== null) {
            return min((int) ceil($dureeMinutes / 25), 6);
        }

        return match ($type) {
            TypeRessource::COURS   => 3,
            TypeRessource::LIVRE   => 4,
            default                => 1,
        };
    }
}
