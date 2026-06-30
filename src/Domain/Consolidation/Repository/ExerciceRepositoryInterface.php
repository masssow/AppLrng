<?php

declare(strict_types=1);

namespace App\Domain\Consolidation\Repository;

use App\Domain\Consolidation\Entity\Exercice;
use Symfony\Component\Uid\Uuid;

interface ExerciceRepositoryInterface
{
    public function findById(Uuid $id): ?Exercice;

    public function save(Exercice $exercice, bool $flush = false): void;
}
