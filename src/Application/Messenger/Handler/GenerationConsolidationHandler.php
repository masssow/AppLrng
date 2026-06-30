<?php

declare(strict_types=1);

namespace App\Application\Messenger\Handler;

use App\Application\Consolidation\Service\ConsolidationService;
use App\Application\IA\AIOrchestrator;
use App\Application\Messenger\Message\GenerationConsolidationMessage;
use App\Domain\Consolidation\Entity\SessionConsolidation;
use App\Domain\Consolidation\Enum\StatutSession;
use App\Domain\Consolidation\Event\ConsolidationPreteEvent;
use App\Domain\Consolidation\Repository\SessionConsolidationRepositoryInterface;
use App\Domain\Consolidation\Repository\TraceApprentissageRepositoryInterface;
use App\Infrastructure\IA\Exception\AIGenerationException;
use App\Infrastructure\IA\Exception\InvalidDTOException;
use App\Infrastructure\IA\Exception\ParseException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
class GenerationConsolidationHandler
{
    public function __construct(
        private readonly SessionConsolidationRepositoryInterface $sessionRepo,
        private readonly TraceApprentissageRepositoryInterface   $traceRepo,
        private readonly AIOrchestrator                          $aiOrchestrator,
        private readonly ConsolidationService                    $consolidationService,
        private readonly EventDispatcherInterface                $eventDispatcher,
        private readonly EntityManagerInterface                  $em,
    ) {}

    public function __invoke(GenerationConsolidationMessage $message): void
    {
        $session = $this->sessionRepo->findById(Uuid::fromString($message->sessionConsolidationId));
        $trace   = $this->traceRepo->findById(Uuid::fromString($message->traceApprentissageId));

        if ($session === null || $trace === null) {
            throw new UnrecoverableMessageHandlingException('Session ou trace introuvable.');
        }

        $sessionUser = $session->getRessource()->getParcours()->getUser();
        $traceUser   = $trace->getUser();
        if ($sessionUser !== $traceUser) {
            throw new UnrecoverableMessageHandlingException('Incohérence ownership session/trace.');
        }

        $session->setStatut(StatutSession::GENERATION);
        $this->em->flush();

        try {
            $domaine = $session->getRessource()->getParcours()->getDomaine()->getStrategieKey();
            $niveau  = $session->getRessource()->getParcours()->getNiveau();
            $dto     = $this->aiOrchestrator->genererConsolidation($trace, $domaine, $niveau);

            $this->consolidationService->persisterResultatIA($session, $dto);

            $session->setStatut(StatutSession::PRET);
            $session->setGeneratedAt(new \DateTime());
            $this->em->flush();

            $this->eventDispatcher->dispatch(new ConsolidationPreteEvent($session));

        } catch (AIGenerationException $e) {
            throw $e;
        } catch (ParseException|InvalidDTOException $e) {
            $session->setStatut(StatutSession::ERREUR);
            $session->setGenerationError($e->getMessage());
            $this->em->flush();
            throw new UnrecoverableMessageHandlingException($e->getMessage(), 0, $e);
        }
    }
}
