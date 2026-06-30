<?php

declare(strict_types=1);

namespace App\Application\Revision\Service;

use App\Domain\Parcours\Entity\Ressource;
use App\Domain\Revision\Entity\RevisionSpacee;
use App\Domain\Revision\Repository\RevisionSpaceeRepositoryInterface;
use App\Domain\Shared\Entity\User;

class RevisionService
{
    private const INTERVALLES = [
        1 => 1,
        2 => 3,
        3 => 7,
        4 => 14,
        5 => 30,
    ];

    public function __construct(
        private readonly RevisionSpaceeRepositoryInterface $revisionRepository,
    ) {}

    public function creerPremiere(Ressource $ressource, User $user): RevisionSpacee
    {
        $datePrevue = $this->calculerDatePrevue(1);
        $revision   = new RevisionSpacee($ressource, $user, 1, $datePrevue);
        $this->revisionRepository->save($revision, true);

        return $revision;
    }

    public function completer(RevisionSpacee $revision, int $score): ?RevisionSpacee
    {
        $revision->setCompleteeAt(new \DateTimeImmutable());
        $revision->setScore($score);
        $this->revisionRepository->save($revision, true);

        $prochaineIteration = $revision->getIteration() + 1;
        if ($prochaineIteration > 5) {
            return null;
        }

        $jours = $this->calculerIntervalleEffectif($prochaineIteration, $score);
        $datePrevue = new \DateTimeImmutable('+' . $jours . ' days');

        $suivante = new RevisionSpacee(
            $revision->getRessource(),
            $revision->getUser(),
            $prochaineIteration,
            $datePrevue,
        );
        $this->revisionRepository->save($suivante, true);

        return $suivante;
    }

    public function calculerDatePrevue(int $iteration, int $score = 5): \DateTimeImmutable
    {
        $jours = $this->calculerIntervalleEffectif($iteration, $score);

        return new \DateTimeImmutable('+' . $jours . ' days');
    }

    private function calculerIntervalleEffectif(int $iteration, int $score): int
    {
        $jours = self::INTERVALLES[$iteration] ?? 30;

        if ($score < 3) {
            $jours = max(1, (int) ceil($jours / 2));
        }

        return $jours;
    }
}
