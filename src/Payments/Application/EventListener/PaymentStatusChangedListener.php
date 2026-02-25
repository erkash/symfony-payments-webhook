<?php

declare(strict_types=1);

namespace App\Payments\Application\EventListener;

use App\Payments\Domain\Event\PaymentStatusChanged;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class PaymentStatusChangedListener
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(PaymentStatusChanged $event): void
    {
        $this->logger->info('Payment status changed', [
            'paymentId' => $event->paymentId->toRfc4122(),
            'previousStatus' => $event->previousStatus->value,
            'newStatus' => $event->newStatus->value,
            'occurredOn' => $event->occurredOn()->format(DATE_ATOM),
        ]);
    }
}
