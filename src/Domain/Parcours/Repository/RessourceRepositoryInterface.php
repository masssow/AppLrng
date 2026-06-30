<?php

declare(strict_types=1);

namespace App\Domain\Parcours\Repository;

use App\Domain\Parcours\Entity\Ressource;
use App\Domain\Shared\Entity\User;
use Symfony\Component\Uid\Uuid;

interface RessourceRepositoryInterface
{
    public function findById(Uuid $id): ?Ressource;

    public function findOneForUser(Uuid $id, User $user): ?Ressource;

    /** @return Ressource[] */
    public function findByParcoursForUser(Uuid $parcoursId, User $user): array;

    public function save(Ressource $ressource, bool $flush = false): void;
}
