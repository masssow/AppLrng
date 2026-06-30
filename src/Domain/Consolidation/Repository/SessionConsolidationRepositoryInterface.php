<?php

declare(strict_types=1);

namespace App\Domain\Consolidation\Repository;

use App\Domain\Consolidation\Entity\SessionConsolidation;
use App\Domain\Parcours\Entity\Ressource;
use App\Domain\Shared\Entity\User;
use Symfony\Component\Uid\Uuid;

interface SessionConsolidationRepositoryInterface
{
    public function findById(Uuid $id): ?SessionConsolidation;

    /** @return SessionConsolidation[] */
    public function findByRessourceForUser(Uuid $ressourceId, User $user): array;

    public function findLastCompleteForRessource(Ressource $ressource): ?SessionConsolidation;

    public function findPretPourUser(User $user): ?SessionConsolidation;

    /** @return string[] dates au format 'Y-m-d' */
    public function findDatesCompletionPourUser(User $user, \DateTime $depuis): array;

    public function save(SessionConsolidation $session, bool $flush = false): void;
}
