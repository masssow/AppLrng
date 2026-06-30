<?php

declare(strict_types=1);

namespace App\Application\Messenger\Handler;

use App\Application\IA\AIOrchestrator;
use App\Application\Messenger\Message\GenerationParcoursMessage;
use App\Application\Parcours\Service\ParcoursService;
use App\Domain\Parcours\Enum\StatutParcours;
use App\Domain\Parcours\Event\ParcoursStructureEvent;
use App\Domain\Parcours\Repository\ParcoursRepositoryInterface;
use App\Infrastructure\IA\Exception\AIGenerationException;
use App\Infrastructure\IA\Exception\InvalidDTOException;
use App\Infrastructure\IA\Exception\ParseException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
class GenerationParcoursHandler
{
    public function __construct(
        private readonly ParcoursRepositoryInterface $parcoursRepo,
        private readonly AIOrchestrator              $aiOrchestrator,
        private readonly ParcoursService             $parcoursService,
        private readonly EventDispatcherInterface    $eventDispatcher,
        private readonly EntityManagerInterface      $em,
    ) {}

    public function __invoke(GenerationParcoursMessage $message): void
    {
        $parcours = $this->parcoursRepo->findById(Uuid::fromString($message->parcoursId));

        if ($parcours === null) {
            throw new UnrecoverableMessageHandlingException('Parcours introuvable.');
        }

        try {
            $ressources = $parcours->getRessources()->toArray();
            $titres     = array_map(
                fn($r) => ['ref' => 'r' . $r->getOrdre(), 'titre' => $r->getTitre()],
                $ressources
            );

            $dto = $this->aiOrchestrator->structurerParcours(
                $parcours->getObjectif(),
                $parcours->getNiveau(),
                $parcours->getDomaine()->getStrategieKey(),
                $titres
            );

            $this->parcoursService->appliquerStructureIA($parcours, $dto);

            $parcours->setStatut(StatutParcours::ACTIF);
            $this->em->flush();

            $this->eventDispatcher->dispatch(new ParcoursStructureEvent($parcours));

        } catch (AIGenerationException $e) {
            throw $e;
        } catch (ParseException|InvalidDTOException $e) {
            $parcours->setStatut(StatutParcours::BROUILLON);
            $this->em->flush();
            throw new UnrecoverableMessageHandlingException($e->getMessage(), 0, $e);
        }
    }
}
