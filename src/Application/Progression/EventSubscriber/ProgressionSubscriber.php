<?php

declare(strict_types=1);

namespace App\Application\Progression\EventSubscriber;

use App\Application\Progression\Service\ProgressionCalculator;
use App\Domain\Consolidation\Event\RessourceConsolideeEvent;
use App\Domain\Parcours\Event\ProjetTermineEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProgressionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ProgressionCalculator $progressionCalculator,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            RessourceConsolideeEvent::class => 'onRessourceConsolidee',
            ProjetTermineEvent::class       => 'onProjetTermine',
        ];
    }

    public function onRessourceConsolidee(RessourceConsolideeEvent $event): void
    {
        $this->progressionCalculator->recalculer(
            $event->ressource->getParcours()
        );
    }

    public function onProjetTermine(ProjetTermineEvent $event): void
    {
        $this->progressionCalculator->recalculer($event->parcours);
    }
}
