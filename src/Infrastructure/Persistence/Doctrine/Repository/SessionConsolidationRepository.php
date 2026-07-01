<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Consolidation\Entity\SessionConsolidation;
use App\Domain\Consolidation\Enum\StatutSession;
use App\Domain\Consolidation\Repository\SessionConsolidationRepositoryInterface;
use App\Domain\Parcours\Entity\Ressource;
use App\Domain\Shared\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class SessionConsolidationRepository extends ServiceEntityRepository implements SessionConsolidationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionConsolidation::class);
    }

    public function findById(Uuid $id): ?SessionConsolidation
    {
        return $this->createQueryBuilder('s')
            ->where('s.id = :id')
            ->setParameter('id', $id, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByRessourceForUser(Uuid $ressourceId, User $user): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.ressource', 'r')
            ->join('r.parcours', 'p')
            ->where('r.id = :ressourceId')
            ->andWhere('p.user = :user')
            ->orderBy('s.createdAt', 'DESC')
            ->setParameter('ressourceId', $ressourceId, 'uuid')
            ->setParameter('user', $user->getId(), 'uuid')
            ->getQuery()
            ->getResult();
    }

    public function findLastCompleteForRessource(Ressource $ressource): ?SessionConsolidation
    {
        return $this->createQueryBuilder('s')
            ->where('s.ressource = :ressource')
            ->andWhere('s.statut = :statut')
            ->orderBy('s.completedAt', 'DESC')
            ->setMaxResults(1)
            ->setParameter('ressource', $ressource)
            ->setParameter('statut', StatutSession::COMPLETE)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPretPourUser(User $user): ?SessionConsolidation
    {
        return $this->createQueryBuilder('s')
            ->join('s.ressource', 'r')
            ->join('r.parcours', 'p')
            ->where('p.user = :user')
            ->andWhere('s.statut = :statut')
            ->orderBy('s.createdAt', 'ASC')
            ->setMaxResults(1)
            ->setParameter('user', $user->getId(), 'uuid')
            ->setParameter('statut', \App\Domain\Consolidation\Enum\StatutSession::PRET)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDatesCompletionPourUser(User $user, \DateTime $depuis): array
    {
        $rows = $this->createQueryBuilder('s')
            ->select('s.completedAt')
            ->join('s.ressource', 'r')
            ->join('r.parcours', 'p')
            ->where('p.user = :user')
            ->andWhere('s.completedAt IS NOT NULL')
            ->andWhere('s.completedAt >= :depuis')
            ->setParameter('user', $user->getId(), 'uuid')
            ->setParameter('depuis', $depuis)
            ->getQuery()
            ->getArrayResult();

        return array_unique(array_map(
            static fn(array $row): string => $row['completedAt']->format('Y-m-d'),
            $rows
        ));
    }

    public function save(SessionConsolidation $session, bool $flush = false): void
    {
        $this->getEntityManager()->persist($session);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
