<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Consolidation\Entity\Exercice;
use App\Domain\Consolidation\Repository\ExerciceRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class ExerciceRepository extends ServiceEntityRepository implements ExerciceRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exercice::class);
    }

    public function findById(Uuid $id): ?Exercice
    {
        return $this->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', $id, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Exercice $exercice, bool $flush = false): void
    {
        $this->getEntityManager()->persist($exercice);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
