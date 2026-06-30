<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Consolidation\Entity\TraceApprentissage;
use App\Domain\Consolidation\Repository\TraceApprentissageRepositoryInterface;
use App\Domain\Shared\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class TraceApprentissageRepository extends ServiceEntityRepository implements TraceApprentissageRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TraceApprentissage::class);
    }

    public function findById(Uuid $id): ?TraceApprentissage
    {
        return $this->createQueryBuilder('t')
            ->where('t.id = :id')
            ->setParameter('id', $id, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByRessourceForUser(Uuid $ressourceId, User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.ressource = :ressourceId')
            ->andWhere('t.user = :user')
            ->orderBy('t.createdAt', 'DESC')
            ->setParameter('ressourceId', $ressourceId, 'uuid')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function save(TraceApprentissage $trace, bool $flush = false): void
    {
        $this->getEntityManager()->persist($trace);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
