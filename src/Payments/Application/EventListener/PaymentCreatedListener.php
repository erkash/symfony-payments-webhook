<?php

declare(strict_types=1);

namespace App\Payments\Application\EventListener;

use App\Payments\Domain\Event\PaymentCreated;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class PaymentCreatedListener
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(PaymentCreated $event): void
    {
        $this->logger->info('Payment created', [
            'paymentId' => $event->paymentId->toRfc4122(),
            'amount' => $event->amount,
            'currency' => $event->currency,
            'status' => $event->status->value,
            'occurredOn' => $event->occurredOn()->format(DATE_ATOM),
        ]);
    }
}
