<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Application\Dashboard\DTO\ReprendreDTO;
use App\Application\Dashboard\DTO\ScoreAgregeDTO;
use App\Application\Dashboard\Service\DashboardService;
use App\Domain\Consolidation\Entity\Question;
use App\Domain\Consolidation\Entity\SessionConsolidation;
use App\Domain\Consolidation\Repository\SessionConsolidationRepositoryInterface;
use App\Domain\Parcours\Entity\Parcours;
use App\Domain\Parcours\Entity\Ressource;
use App\Domain\Parcours\Repository\ParcoursRepositoryInterface;
use App\Domain\Progression\Entity\Progression;
use App\Domain\Projet\Entity\MicroEtape;
use App\Domain\Projet\Repository\MicroEtapeRepositoryInterface;
use App\Domain\Revision\Repository\RevisionSpaceeRepositoryInterface;
use App\Domain\Shared\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DashboardServiceTest extends TestCase
{
    private ParcoursRepositoryInterface&MockObject             $parcoursRepo;
    private SessionConsolidationRepositoryInterface&MockObject $sessionRepo;
    private MicroEtapeRepositoryInterface&MockObject           $microEtapeRepo;
    private RevisionSpaceeRepositoryInterface&MockObject       $revisionRepo;
    private DashboardService                                   $service;
    private User&MockObject                                    $user;

    protected function setUp(): void
    {
        $this->parcoursRepo   = $this->createMock(ParcoursRepositoryInterface::class);
        $this->sessionRepo    = $this->createMock(SessionConsolidationRepositoryInterface::class);
        $this->microEtapeRepo = $this->createMock(MicroEtapeRepositoryInterface::class);
        $this->revisionRepo   = $this->createMock(RevisionSpaceeRepositoryInterface::class);

        $this->service = new DashboardService(
            $this->parcoursRepo,
            $this->sessionRepo,
            $this->microEtapeRepo,
            $this->revisionRepo,
        );

        $this->user = $this->createMock(User::class);
    }

    // ─── getActionAReprendre ─────────────────────────────────────────────────

    public function testGetActionAReprendreReturnsNullWhenNothingPending(): void
    {
        $this->sessionRepo->method('findPretPourUser')->willReturn(null);
        $this->microEtapeRepo->method('findEnCoursPourUser')->willReturn(null);
        $this->parcoursRepo->method('findByUserOrderedByDate')->willReturn([]);

        self::assertNull($this->service->getActionAReprendre($this->user));
    }

    public function testGetActionAReprendrePrioritisesConsolidationFirst(): void
    {
        $session  = $this->createMock(SessionConsolidation::class);
        $ressource = $this->createMock(Ressource::class);
        $parcours  = $this->createMock(Parcours::class);
        $question  = $this->createMock(Question::class);

        $question->method('getReponseUtilisateur')->willReturn(null);
        $session->method('getQuestions')->willReturn([$question]);
        $session->method('getExercice')->willReturn(null);
        $session->method('getRessource')->willReturn($ressource);
        $session->method('getId')->willReturn(\Symfony\Component\Uid\Uuid::v4());
        $ressource->method('getTitre')->willReturn('Apprendre Excel');
        $ressource->method('getParcours')->willReturn($parcours);
        $parcours->method('getTitre')->willReturn('Spécialisation Excel');

        $this->sessionRepo->method('findPretPourUser')->willReturn($session);

        $result = $this->service->getActionAReprendre($this->user);

        self::assertInstanceOf(ReprendreDTO::class, $result);
        self::assertSame('consolidation', $result->type);
        self::assertSame('Apprendre Excel', $result->titre);
        self::assertSame('app_consolidation_show', $result->route);
    }

    public function testGetActionAReprendreFallsBackToMicroEtapeWhenNoSession(): void
    {
        $this->sessionRepo->method('findPretPourUser')->willReturn(null);

        $etape   = $this->createMock(MicroEtape::class);
        $projet  = $this->createMock(\App\Domain\Projet\Entity\ProjetFilRouge::class);
        $parcours = $this->createMock(Parcours::class);

        $etape->method('getTitre')->willReturn('Créer un tableau');
        $etape->method('getProjet')->willReturn($projet);
        $etape->method('getId')->willReturn(\Symfony\Component\Uid\Uuid::v4());
        $projet->method('getParcours')->willReturn($parcours);
        $parcours->method('getTitre')->willReturn('Spécialisation Excel');

        $this->microEtapeRepo->method('findEnCoursPourUser')->willReturn($etape);

        $result = $this->service->getActionAReprendre($this->user);

        self::assertInstanceOf(ReprendreDTO::class, $result);
        self::assertSame('micro_etape', $result->type);
        self::assertSame('app_projet_etape_rendu', $result->route);
    }

    public function testGetActionAReprendreFallsBackToRessourceWhenNoEtape(): void
    {
        $this->sessionRepo->method('findPretPourUser')->willReturn(null);
        $this->microEtapeRepo->method('findEnCoursPourUser')->willReturn(null);

        $ressource = $this->createMock(Ressource::class);
        $parcours  = $this->createMock(Parcours::class);

        $ressource->method('getStatut')->willReturn(\App\Domain\Parcours\Enum\StatutRessource::EN_COURS);
        $ressource->method('getTitre')->willReturn('Apprendre Excel');
        $ressource->method('getId')->willReturn(\Symfony\Component\Uid\Uuid::v4());
        $parcours->method('getRessources')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([$ressource]));
        $parcours->method('getTitre')->willReturn('Spécialisation Excel');

        $this->parcoursRepo->method('findByUserOrderedByDate')->willReturn([$parcours]);

        $result = $this->service->getActionAReprendre($this->user);

        self::assertInstanceOf(ReprendreDTO::class, $result);
        self::assertSame('ressource', $result->type);
        self::assertSame('app_ressource_show', $result->route);
    }

    // ─── getStreak ───────────────────────────────────────────────────────────

    public function testGetStreakReturnsZeroWithNoActivity(): void
    {
        $this->sessionRepo->method('findDatesCompletionPourUser')->willReturn([]);
        $this->revisionRepo->method('findDatesCompletionPourUser')->willReturn([]);
        $this->microEtapeRepo->method('findDatesCompletionPourUser')->willReturn([]);

        self::assertSame(0, $this->service->getStreak($this->user));
    }

    public function testGetStreakCountsConsecutiveDays(): void
    {
        $today     = (new \DateTime())->format('Y-m-d');
        $yesterday = (new \DateTime('-1 day'))->format('Y-m-d');
        $twoDays   = (new \DateTime('-2 days'))->format('Y-m-d');

        $this->sessionRepo->method('findDatesCompletionPourUser')->willReturn([$today, $yesterday]);
        $this->revisionRepo->method('findDatesCompletionPourUser')->willReturn([$twoDays]);
        $this->microEtapeRepo->method('findDatesCompletionPourUser')->willReturn([]);

        self::assertSame(3, $this->service->getStreak($this->user));
    }

    public function testGetStreakToleratesOneDayGap(): void
    {
        // Si aujourd'hui pas d'activité mais hier oui → streak continue depuis hier
        $yesterday = (new \DateTime('-1 day'))->format('Y-m-d');
        $twoDays   = (new \DateTime('-2 days'))->format('Y-m-d');

        $this->sessionRepo->method('findDatesCompletionPourUser')->willReturn([$yesterday, $twoDays]);
        $this->revisionRepo->method('findDatesCompletionPourUser')->willReturn([]);
        $this->microEtapeRepo->method('findDatesCompletionPourUser')->willReturn([]);

        self::assertSame(2, $this->service->getStreak($this->user));
    }

    public function testGetStreakBreaksOnGap(): void
    {
        $today   = (new \DateTime())->format('Y-m-d');
        $twoDays = (new \DateTime('-2 days'))->format('Y-m-d'); // hier manquant → rupture

        $this->sessionRepo->method('findDatesCompletionPourUser')->willReturn([$today, $twoDays]);
        $this->revisionRepo->method('findDatesCompletionPourUser')->willReturn([]);
        $this->microEtapeRepo->method('findDatesCompletionPourUser')->willReturn([]);

        self::assertSame(1, $this->service->getStreak($this->user));
    }

    // ─── getScoreAgrege ──────────────────────────────────────────────────────

    public function testGetScoreAgregeReturnsNullWithNoParcours(): void
    {
        $this->parcoursRepo->method('findActiveForUser')->willReturn([]);

        self::assertNull($this->service->getScoreAgrege($this->user));
    }

    public function testGetScoreAgregeCalculatesWeightedAverage(): void
    {
        // Parcours A : 10 ressources, score 80%
        // Parcours B : 2 ressources, score 20%
        // Moyenne pondérée = (80*10 + 20*2) / 12 = 840/12 = 70
        $progressionA = $this->createMock(Progression::class);
        $progressionA->method('getRessourcesTotal')->willReturn(10);
        $progressionA->method('getScoreConsolidation')->willReturn(80);

        $progressionB = $this->createMock(Progression::class);
        $progressionB->method('getRessourcesTotal')->willReturn(2);
        $progressionB->method('getScoreConsolidation')->willReturn(20);

        $parcoursA = $this->createMock(Parcours::class);
        $parcoursA->method('getProgression')->willReturn($progressionA);

        $parcoursB = $this->createMock(Parcours::class);
        $parcoursB->method('getProgression')->willReturn($progressionB);

        $this->parcoursRepo->method('findActiveForUser')->willReturn([$parcoursA, $parcoursB]);

        $result = $this->service->getScoreAgrege($this->user);

        self::assertInstanceOf(ScoreAgregeDTO::class, $result);
        self::assertSame(70, $result->valeur);
        self::assertSame('Bonne progression', $result->libelleQualitatif);
    }

    public function testGetScoreAgregeLibellesAreCorrect(): void
    {
        $cases = [
            [85, 'Excellente maîtrise'],
            [65, 'Bonne progression'],
            [40, 'En cours de construction'],
            [10, 'Tout début de parcours'],
        ];

        foreach ($cases as [$score, $libelle]) {
            $progression = $this->createMock(Progression::class);
            $progression->method('getRessourcesTotal')->willReturn(5);
            $progression->method('getScoreConsolidation')->willReturn($score);

            $parcours = $this->createMock(Parcours::class);
            $parcours->method('getProgression')->willReturn($progression);

            $this->parcoursRepo->method('findActiveForUser')->willReturn([$parcours]);

            $result = $this->service->getScoreAgrege($this->user);
            self::assertSame($libelle, $result?->libelleQualitatif, "Échec pour score $score");

            // Réinitialiser le mock pour le prochain cas
            $this->parcoursRepo = $this->createMock(ParcoursRepositoryInterface::class);
            $this->rebuildService();
        }
    }

    private function rebuildService(): void
    {
        $this->service = new DashboardService(
            $this->parcoursRepo,
            $this->sessionRepo,
            $this->microEtapeRepo,
            $this->revisionRepo,
        );
    }
}
