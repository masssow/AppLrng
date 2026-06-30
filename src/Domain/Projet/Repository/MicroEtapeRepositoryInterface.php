<?php

declare(strict_types=1);

namespace App\Domain\Projet\Repository;

use App\Domain\Projet\Entity\MicroEtape;
use App\Domain\Projet\Entity\ProjetFilRouge;
use Symfony\Component\Uid\Uuid;

interface MicroEtapeRepositoryInterface
{
    public function findById(Uuid $id): ?MicroEtape;

    /** @return MicroEtape[] */
    public function findByProjet(ProjetFilRouge $projet): array;

    public function findSuivante(MicroEtape $etape): ?MicroEtape;

    public function findEnCoursPourUser(\App\Domain\Shared\Entity\User $user): ?MicroEtape;

    /** @return string[] dates au format 'Y-m-d' */
    public function findDatesCompletionPourUser(\App\Domain\Shared\Entity\User $user, \DateTime $depuis): array;

    public function save(MicroEtape $etape, bool $flush = false): void;
}
