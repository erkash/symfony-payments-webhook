<?php

declare(strict_types=1);

namespace App\Payments\Domain\Event;

use App\Payments\Domain\PaymentStatus;
use Symfony\Component\Uid\Uuid;

final readonly class PaymentStatusChanged implements DomainEventInterface
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        public Uuid $paymentId,
        public PaymentStatus $previousStatus,
        public PaymentStatus $newStatus,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
