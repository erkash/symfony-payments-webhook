<?php

declare(strict_types=1);

namespace App\Payments\Domain\Event;

interface RecordsEventsInterface
{
    /**
     * @return DomainEventInterface[]
     */
    public function releaseEvents(): array;
}
