<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Parcours\Entity\Ressource;
use App\Domain\Parcours\Repository\RessourceRepositoryInterface;
use App\Domain\Shared\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class RessourceRepository extends ServiceEntityRepository implements RessourceRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ressource::class);
    }

    public function findById(Uuid $id): ?Ressource
    {
        return $this->createQueryBuilder('r')
            ->where('r.id = :id')
            ->setParameter('id', $id, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneForUser(Uuid $id, User $user): ?Ressource
    {
        return $this->createQueryBuilder('r')
            ->join('r.parcours', 'p')
            ->where('r.id = :id')
            ->andWhere('p.user = :user')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('id', $id, 'uuid')
            ->setParameter('user', $user->getId(), 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByParcoursForUser(Uuid $parcoursId, User $user): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.parcours', 'p')
            ->where('p.id = :parcoursId')
            ->andWhere('p.user = :user')
            ->orderBy('r.ordre', 'ASC')
            ->setParameter('parcoursId', $parcoursId, 'uuid')
            ->setParameter('user', $user->getId(), 'uuid')
            ->getQuery()
            ->getResult();
    }

    public function save(Ressource $ressource, bool $flush = false): void
    {
        $this->getEntityManager()->persist($ressource);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
