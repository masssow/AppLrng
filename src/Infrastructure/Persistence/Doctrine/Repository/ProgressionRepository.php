<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Parcours\Entity\Parcours;
use App\Domain\Progression\Entity\Progression;
use App\Domain\Progression\Repository\ProgressionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProgressionRepository extends ServiceEntityRepository implements ProgressionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Progression::class);
    }

    public function findByParcours(Parcours $parcours): ?Progression
    {
        return $this->createQueryBuilder('p')
            ->where('p.parcours = :parcours')
            ->setParameter('parcours', $parcours)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Progression $progression, bool $flush = false): void
    {
        $this->getEntityManager()->persist($progression);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
