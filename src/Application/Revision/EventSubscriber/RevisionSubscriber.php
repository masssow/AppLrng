<?php

declare(strict_types=1);

namespace App\Application\Revision\EventSubscriber;

use App\Domain\Consolidation\Event\RessourceConsolideeEvent;
use App\Domain\Revision\Entity\RevisionSpacee;
use App\Domain\Revision\Repository\RevisionSpaceeRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RevisionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RevisionSpaceeRepositoryInterface $revisionRepository,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            RessourceConsolideeEvent::class => 'onRessourceConsolidee',
        ];
    }

    public function onRessourceConsolidee(RessourceConsolideeEvent $event): void
    {
        $ressource = $event->ressource;
        $user      = $ressource->getParcours()->getUser();

        $revision = new RevisionSpacee(
            ressource: $ressource,
            user: $user,
            iteration: 1,
            datePrevue: (new \DateTimeImmutable())->modify('+1 day'),
        );

        $this->revisionRepository->save($revision, true);
    }
}
