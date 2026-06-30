<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Parcours\Entity\Parcours;
use App\Domain\Parcours\Enum\StatutParcours;
use App\Domain\Parcours\Repository\ParcoursRepositoryInterface;
use App\Domain\Shared\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class ParcoursRepository extends ServiceEntityRepository implements ParcoursRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parcours::class);
    }

    public function findById(Uuid $id): ?Parcours
    {
        return $this->createQueryBuilder('p')
            ->where('p.id = :id')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('id', $id, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneForUser(Uuid $id, User $user): ?Parcours
    {
        return $this->createQueryBuilder('p')
            ->where('p.id = :id')
            ->andWhere('p.user = :user')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('id', $id, 'uuid')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUserOrderedByDate(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.deletedAt IS NULL')
            ->orderBy('p.createdAt', 'DESC')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findActiveForUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.statut = :statut')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('statut', StatutParcours::ACTIF)
            ->getQuery()
            ->getResult();
    }

    public function save(Parcours $parcours, bool $flush = false): void
    {
        $this->getEntityManager()->persist($parcours);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Parcours $parcours, bool $flush = false): void
    {
        $parcours->setDeletedAt(new \DateTime());

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
