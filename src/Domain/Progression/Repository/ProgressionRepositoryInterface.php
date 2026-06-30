<?php

declare(strict_types=1);

namespace App\Domain\Progression\Repository;

use App\Domain\Parcours\Entity\Parcours;
use App\Domain\Progression\Entity\Progression;

interface ProgressionRepositoryInterface
{
    public function findByParcours(Parcours $parcours): ?Progression;

    public function save(Progression $progression, bool $flush = false): void;
}
