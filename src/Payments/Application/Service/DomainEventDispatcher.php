<?php

declare(strict_types=1);

namespace App\Payments\Application\Service;

use App\Payments\Domain\Event\RecordsEventsInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class DomainEventDispatcher
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function dispatch(RecordsEventsInterface $aggregate): void
    {
        foreach ($aggregate->releaseEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
