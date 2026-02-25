<?php

declare(strict_types=1);

namespace App\Payments\Domain\Event;

use App\Payments\Domain\PaymentStatus;
use App\Payments\Domain\ValueObject\Money;
use Symfony\Component\Uid\Uuid;

final readonly class PaymentCreated implements DomainEventInterface
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        public Uuid $paymentId,
        public Money $money,
        public PaymentStatus $status,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
