<?php

declare(strict_types=1);

namespace App\Domain\Consolidation\Repository;

use App\Domain\Consolidation\Entity\Question;
use Symfony\Component\Uid\Uuid;

interface QuestionRepositoryInterface
{
    public function findById(Uuid $id): ?Question;

    public function save(Question $question, bool $flush = false): void;
}
