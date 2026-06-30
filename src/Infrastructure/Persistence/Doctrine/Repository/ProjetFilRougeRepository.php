<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Projet\Entity\ProjetFilRouge;
use App\Domain\Projet\Repository\ProjetFilRougeRepositoryInterface;
use App\Domain\Shared\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class ProjetFilRougeRepository extends ServiceEntityRepository implements ProjetFilRougeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjetFilRouge::class);
    }

    public function findById(Uuid $id): ?ProjetFilRouge
    {
        return $this->createQueryBuilder('p')
            ->where('p.id = :id')
            ->setParameter('id', $id, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByParcoursForUser(Uuid $parcoursId, User $user): ?ProjetFilRouge
    {
        return $this->createQueryBuilder('p')
            ->join('p.parcours', 'pc')
            ->where('pc.id = :parcoursId')
            ->andWhere('pc.user = :user')
            ->andWhere('pc.deletedAt IS NULL')
            ->setParameter('parcoursId', $parcoursId, 'uuid')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(ProjetFilRouge $projet, bool $flush = false): void
    {
        $this->getEntityManager()->persist($projet);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
