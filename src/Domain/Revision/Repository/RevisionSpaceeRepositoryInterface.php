<?php

declare(strict_types=1);

namespace App\Domain\Revision\Repository;

use App\Domain\Parcours\Entity\Ressource;
use App\Domain\Revision\Entity\RevisionSpacee;
use App\Domain\Shared\Entity\User;
use Symfony\Component\Uid\Uuid;

interface RevisionSpaceeRepositoryInterface
{
    public function findById(Uuid $id): ?RevisionSpacee;

    /** @return RevisionSpacee[] */
    public function findPendingForUser(User $user, \DateTimeInterface $date): array;

    /** @return RevisionSpacee[] */
    public function findByRessource(Ressource $ressource): array;

    /** @return string[] dates au format 'Y-m-d' */
    public function findDatesCompletionPourUser(User $user, \DateTime $depuis): array;

    public function save(RevisionSpacee $revision, bool $flush = false): void;
}
