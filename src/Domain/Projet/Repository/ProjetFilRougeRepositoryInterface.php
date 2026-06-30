<?php

declare(strict_types=1);

namespace App\Domain\Projet\Repository;

use App\Domain\Projet\Entity\ProjetFilRouge;
use App\Domain\Shared\Entity\User;
use Symfony\Component\Uid\Uuid;

interface ProjetFilRougeRepositoryInterface
{
    public function findById(Uuid $id): ?ProjetFilRouge;

    public function findByParcoursForUser(Uuid $parcoursId, User $user): ?ProjetFilRouge;

    public function save(ProjetFilRouge $projet, bool $flush = false): void;
}
