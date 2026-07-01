<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Projet\Entity\MicroEtape;
use App\Domain\Projet\Entity\ProjetFilRouge;
use App\Domain\Projet\Enum\StatutEtape;
use App\Domain\Projet\Repository\MicroEtapeRepositoryInterface;
use App\Domain\Shared\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class MicroEtapeRepository extends ServiceEntityRepository implements MicroEtapeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MicroEtape::class);
    }

    public function findById(Uuid $id): ?MicroEtape
    {
        return $this->createQueryBuilder('e')
            ->where('e.id = :id')
            ->setParameter('id', $id, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByProjet(ProjetFilRouge $projet): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.projet = :projet')
            ->orderBy('e.ordre', 'ASC')
            ->setParameter('projet', $projet)
            ->getQuery()
            ->getResult();
    }

    public function findSuivante(MicroEtape $etape): ?MicroEtape
    {
        return $this->createQueryBuilder('e')
            ->where('e.projet = :projet')
            ->andWhere('e.ordre = :ordre')
            ->setParameter('projet', $etape->getProjet())
            ->setParameter('ordre', $etape->getOrdre() + 1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findEnCoursPourUser(User $user): ?MicroEtape
    {
        return $this->createQueryBuilder('e')
            ->join('e.projet', 'pr')
            ->join('pr.parcours', 'p')
            ->where('p.user = :user')
            ->andWhere('e.statut = :statut')
            ->orderBy('e.debloqueeAt', 'ASC')
            ->setMaxResults(1)
            ->setParameter('user', $user->getId(), 'uuid')
            ->setParameter('statut', StatutEtape::EN_COURS)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDatesCompletionPourUser(User $user, \DateTime $depuis): array
    {
        $rows = $this->createQueryBuilder('e')
            ->select('e.completedAt')
            ->join('e.projet', 'pr')
            ->join('pr.parcours', 'p')
            ->where('p.user = :user')
            ->andWhere('e.completedAt IS NOT NULL')
            ->andWhere('e.completedAt >= :depuis')
            ->setParameter('user', $user->getId(), 'uuid')
            ->setParameter('depuis', $depuis)
            ->getQuery()
            ->getArrayResult();

        return array_unique(array_map(
            static fn(array $row): string => $row['completedAt']->format('Y-m-d'),
            $rows
        ));
    }

    public function save(MicroEtape $etape, bool $flush = false): void
    {
        $this->getEntityManager()->persist($etape);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
