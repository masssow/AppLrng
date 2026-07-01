<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Parcours\Entity\Ressource;
use App\Domain\Revision\Entity\RevisionSpacee;
use App\Domain\Revision\Repository\RevisionSpaceeRepositoryInterface;
use App\Domain\Shared\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class RevisionSpaceeRepository extends ServiceEntityRepository implements RevisionSpaceeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RevisionSpacee::class);
    }

    public function findById(Uuid $id): ?RevisionSpacee
    {
        return $this->createQueryBuilder('r')
            ->where('r.id = :id')
            ->setParameter('id', $id, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPendingForUser(User $user, \DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.datePrevue <= :date')
            ->andWhere('r.completeeAt IS NULL')
            ->orderBy('r.datePrevue', 'ASC')
            ->setParameter('user', $user->getId(), 'uuid')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    public function findByRessource(Ressource $ressource): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.ressource = :ressource')
            ->orderBy('r.iteration', 'ASC')
            ->setParameter('ressource', $ressource)
            ->getQuery()
            ->getResult();
    }

    public function findDatesCompletionPourUser(User $user, \DateTime $depuis): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('r.completeeAt')
            ->where('r.user = :user')
            ->andWhere('r.completeeAt IS NOT NULL')
            ->andWhere('r.completeeAt >= :depuis')
            ->setParameter('user', $user->getId(), 'uuid')
            ->setParameter('depuis', $depuis)
            ->getQuery()
            ->getArrayResult();

        return array_unique(array_map(
            static fn(array $row): string => $row['completeeAt']->format('Y-m-d'),
            $rows
        ));
    }

    public function save(RevisionSpacee $revision, bool $flush = false): void
    {
        $this->getEntityManager()->persist($revision);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
