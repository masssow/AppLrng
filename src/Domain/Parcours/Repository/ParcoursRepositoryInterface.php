<?php

declare(strict_types=1);

namespace App\Domain\Parcours\Repository;

use App\Domain\Parcours\Entity\Parcours;
use App\Domain\Shared\Entity\User;
use Symfony\Component\Uid\Uuid;

interface ParcoursRepositoryInterface
{
    public function findById(Uuid $id): ?Parcours;

    /** @return Parcours[] */
    public function findByUserOrderedByDate(User $user): array;

    /** @return Parcours[] */
    public function findActiveForUser(User $user): array;

    public function findOneForUser(Uuid $id, User $user): ?Parcours;

    public function save(Parcours $parcours, bool $flush = false): void;

    public function remove(Parcours $parcours, bool $flush = false): void;
}
