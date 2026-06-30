<?php

declare(strict_types=1);

namespace App\Domain\Consolidation\Repository;

use App\Domain\Consolidation\Entity\TraceApprentissage;
use App\Domain\Shared\Entity\User;
use Symfony\Component\Uid\Uuid;

interface TraceApprentissageRepositoryInterface
{
    public function findById(Uuid $id): ?TraceApprentissage;

    /** @return TraceApprentissage[] */
    public function findByRessourceForUser(Uuid $ressourceId, User $user): array;

    public function save(TraceApprentissage $trace, bool $flush = false): void;
}
