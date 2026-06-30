<?php

declare(strict_types=1);

namespace App\Application\Messenger\Handler;

use App\Application\IA\AIOrchestrator;
use App\Application\Messenger\Message\EvaluationReponseMessage;
use App\Domain\Consolidation\Enum\StatutEvaluation;
use App\Domain\Consolidation\Repository\ExerciceRepositoryInterface;
use App\Domain\Consolidation\Repository\QuestionRepositoryInterface;
use App\Infrastructure\IA\Exception\AIGenerationException;
use App\Infrastructure\IA\Exception\InvalidDTOException;
use App\Infrastructure\IA\Exception\ParseException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class EvaluationReponseHandler
{
    public function __construct(
        private readonly QuestionRepositoryInterface $questionRepo,
        private readonly ExerciceRepositoryInterface $exerciceRepo,
        private readonly AIOrchestrator              $aiOrchestrator,
        private readonly EntityManagerInterface      $em,
    ) {}

    public function __invoke(EvaluationReponseMessage $message): void
    {
        if ($message->targetType === EvaluationReponseMessage::TYPE_QUESTION) {
            $question = $this->questionRepo->findById(Uuid::fromString($message->targetId));

            if ($question === null) {
                throw new UnrecoverableMessageHandlingException('Question introuvable.');
            }

            $domaine = $question->getSession()->getRessource()->getParcours()->getDomaine()->getStrategieKey();
            $niveau  = $question->getSession()->getRessource()->getParcours()->getNiveau();

            try {
                $dto = $this->aiOrchestrator->evaluerReponse(
                    $question->getTexte(),
                    $question->getReponseUtilisateur() ?? '',
                    $domaine,
                    $niveau
                );
                $question->setFeedbackIa($dto->feedback);
                $question->setFeedbackScore($dto->score);
                $question->setDecision($dto->decision);
                $question->setValidee(true);
                $question->setStatutEvaluation(StatutEvaluation::EVALUEE);
                $this->em->flush();

            } catch (AIGenerationException $e) {
                throw $e;
            } catch (ParseException|InvalidDTOException $e) {
                $question->setStatutEvaluation(StatutEvaluation::ERREUR);
                $this->em->flush();
                throw new UnrecoverableMessageHandlingException($e->getMessage(), 0, $e);
            }

        } elseif ($message->targetType === EvaluationReponseMessage::TYPE_EXERCICE) {
            $exercice = $this->exerciceRepo->findById(Uuid::fromString($message->targetId));

            if ($exercice === null) {
                throw new UnrecoverableMessageHandlingException('Exercice introuvable.');
            }

            $domaine = $exercice->getSession()->getRessource()->getParcours()->getDomaine()->getStrategieKey();
            $niveau  = $exercice->getSession()->getRessource()->getParcours()->getNiveau();

            try {
                $dto = $this->aiOrchestrator->evaluerReponse(
                    $exercice->getEnonce(),
                    $exercice->getRenduUtilisateur() ?? '',
                    $domaine,
                    $niveau
                );
                $exercice->setFeedbackIa($dto->feedback);
                $exercice->setFeedbackScore($dto->score);
                $exercice->setDecision($dto->decision);
                $exercice->setStatutEvaluation(StatutEvaluation::EVALUEE);
                $this->em->flush();

            } catch (AIGenerationException $e) {
                throw $e;
            } catch (ParseException|InvalidDTOException $e) {
                $exercice->setStatutEvaluation(StatutEvaluation::ERREUR);
                $this->em->flush();
                throw new UnrecoverableMessageHandlingException($e->getMessage(), 0, $e);
            }
        }
    }
}
