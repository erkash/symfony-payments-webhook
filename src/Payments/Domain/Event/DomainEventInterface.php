<?php

declare(strict_types=1);

namespace App\Payments\Domain\Event;

interface DomainEventInterface
{
    public function occurredOn(): \DateTimeImmutable;
}
