<?php

declare(strict_types=1);

namespace App\Application\Consolidation\Service;

use App\Application\IA\DTO\ConsolidationDTO;
use App\Application\Messenger\Message\EvaluationReponseMessage;
use App\Application\Messenger\Message\GenerationConsolidationMessage;
use App\Application\Progression\Service\ProgressionCalculator;
use App\Application\Ressource\Service\RessourceService;
use App\Application\Security\OwnershipChecker;
use App\Domain\Consolidation\Entity\Exercice;
use App\Domain\Consolidation\Entity\Question;
use App\Domain\Consolidation\Entity\SessionConsolidation;
use App\Domain\Consolidation\Entity\TraceApprentissage;
use App\Domain\Consolidation\Enum\StatutEvaluation;
use App\Domain\Consolidation\Enum\StatutSession;
use App\Domain\Consolidation\Enum\TypeSession;
use App\Domain\Consolidation\Event\RessourceConsolideeEvent;
use App\Domain\Consolidation\Repository\ExerciceRepositoryInterface;
use App\Domain\Consolidation\Repository\QuestionRepositoryInterface;
use App\Domain\Consolidation\Repository\SessionConsolidationRepositoryInterface;
use App\Domain\Consolidation\Repository\TraceApprentissageRepositoryInterface;
use App\Domain\Parcours\Entity\Ressource;
use App\Domain\Shared\Entity\User;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ConsolidationService
{
    public function __construct(
        private readonly TraceApprentissageRepositoryInterface    $traceRepository,
        private readonly SessionConsolidationRepositoryInterface  $sessionRepository,
        private readonly QuestionRepositoryInterface              $questionRepository,
        private readonly ExerciceRepositoryInterface              $exerciceRepository,
        private readonly RessourceService                         $ressourceService,
        private readonly ProgressionCalculator                    $progressionCalculator,
        private readonly MessageBusInterface                      $bus,
        private readonly EventDispatcherInterface                 $eventDispatcher,
        private readonly OwnershipChecker                         $ownershipChecker,
    ) {}

    /**
     * @param array{compris?: string|null, pointsFlous?: string|null, applicationPossible?: string|null, confiance?: int|null, pomodorosEffectues?: int|null} $traceData
     */
    public function initier(
        Ressource $ressource,
        User $user,
        array $traceData,
    ): SessionConsolidation {
        $this->ownershipChecker->assertRessourceBelongsToUser($ressource, $user);

        $trace = new TraceApprentissage($ressource, $user);
        $trace->setComprisParUtilisateur($traceData['compris'] ?? null);
        $trace->setPointsFlous($traceData['pointsFlous'] ?? null);
        $trace->setApplicationPossible($traceData['applicationPossible'] ?? null);
        $trace->setConfianceUtilisateur($traceData['confiance'] ?? null);
        $trace->setPomodorosEffectues($traceData['pomodorosEffectues'] ?? null);
        $this->traceRepository->save($trace);

        $this->ressourceService->passerVue($ressource, $user);

        $session = new SessionConsolidation(
            ressource: $ressource,
            type: TypeSession::INITIALE,
            promptVersion: 'consolidation_v1.0',
            traceApprentissage: $trace,
        );
        $this->sessionRepository->save($session);

        $this->bus->dispatch(new GenerationConsolidationMessage((string) $session->getId(), (string) $trace->getId()));

        $this->sessionRepository->save($session, true);

        return $session;
    }

    public function soumettreReponseQuestion(Question $question, string $reponse, User $user): void
    {
        $this->ownershipChecker->assertSessionBelongsToUser($question->getSession(), $user);

        $question->setReponseUtilisateur($reponse);
        $question->setStatutEvaluation(StatutEvaluation::EVALUATION);
        $this->sessionRepository->save($question->getSession());

        $this->bus->dispatch(new EvaluationReponseMessage(
            EvaluationReponseMessage::TYPE_QUESTION,
            (string) $question->getId(),
        ));

        $this->sessionRepository->save($question->getSession(), true);
    }

    public function soumettreRenduExercice(Exercice $exercice, string $rendu, User $user): void
    {
        $this->ownershipChecker->assertSessionBelongsToUser($exercice->getSession(), $user);

        $exercice->setRenduUtilisateur($rendu);
        $exercice->setStatutEvaluation(StatutEvaluation::EVALUATION);
        $this->sessionRepository->save($exercice->getSession());

        $this->bus->dispatch(new EvaluationReponseMessage(
            EvaluationReponseMessage::TYPE_EXERCICE,
            (string) $exercice->getId(),
        ));

        $this->sessionRepository->save($exercice->getSession(), true);
    }

    public function terminerSession(SessionConsolidation $session): void
    {
        $scores = [];

        foreach ($session->getQuestions() as $question) {
            if ($question->getFeedbackScore() !== null) {
                $scores[] = $question->getFeedbackScore();
            }
        }

        $exercice = $session->getExercice();
        if ($exercice !== null && $exercice->getFeedbackScore() !== null) {
            $scores[] = $exercice->getFeedbackScore();
        }

        $scoreMoyen = count($scores) > 0
            ? array_sum($scores) / count($scores)
            : 0;

        $session->setStatut(StatutSession::COMPLETE);
        $session->setCompletedAt(new \DateTime());
        $ressource = $session->getRessource();

        $this->ressourceService->passerConsolidee($ressource);
        $this->eventDispatcher->dispatch(new RessourceConsolideeEvent($session, $ressource));

        if ($scoreMoyen < 3) {
            $progression = $ressource->getParcours()->getProgression();
            if ($progression !== null) {
                $this->progressionCalculator->ajouterSujetFragile($progression, $ressource);
            }
        }

        // Toujours recalculer — même score < 3, met à jour ressourcesConsolidees et derniereActivite
        $this->progressionCalculator->recalculer($ressource->getParcours());

        $this->sessionRepository->save($session, true);
    }

    public function persisterResultatIA(SessionConsolidation $session, ConsolidationDTO $dto): void
    {
        foreach ($dto->questions as $index => $questionDTO) {
            $question = new Question($session, $questionDTO->texte, $index + 1);
            $this->questionRepository->save($question);
        }

        if ($dto->exercice !== null) {
            $exercice = new Exercice($session, $dto->exercice->enonce);
            if ($dto->exercice->outilSuggere !== null) {
                $exercice->setOutilSuggere([
                    'nom'          => $dto->exercice->outilSuggere->nom,
                    'url'          => $dto->exercice->outilSuggere->url,
                    'instructions' => $dto->exercice->outilSuggere->instructions,
                ]);
            }
            $this->exerciceRepository->save($exercice);
        }

        $session->setReponseIaBrute([
            'niveau_difficulte'           => $dto->niveauDifficulte,
            'concepts_cibles'             => $dto->conceptsCibles,
            'ressource_suivante_suggeree' => $dto->ressourceSuivanteSuggeree,
        ]);

        $this->sessionRepository->save($session, true);
    }
}
