<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Messenger;

use App\Payments\Application\Message\WebhookEventMessage;
use App\Payments\Application\Service\DomainEventDispatcher;
use App\Payments\Domain\PaymentStatus;
use App\Payments\Infrastructure\Repository\PaymentRepository;
use App\Payments\Infrastructure\Repository\WebhookEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class WebhookEventHandler
{
    public function __construct(
        private WebhookEventRepository $events,
        private PaymentRepository $payments,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private DomainEventDispatcher $eventDispatcher,
    ) {
    }

    public function __invoke(WebhookEventMessage $message): void
    {
        $event = $this->events->findByUuid($message->eventId);
        if (!$event) {
            return;
        }

        if ($event->isProcessed()) {
            return;
        }

        if (!$event->isSignatureValid()) {
            $event->markProcessed();
            $this->entityManager->flush();
            return;
        }

        $payload = $event->getPayload();
        $paymentId = $payload['paymentId'] ?? $payload['payment_id'] ?? null;
        if (!is_string($paymentId)) {
            $event->markProcessed();
            $this->entityManager->flush();
            return;
        }

        try {
            $uuid = Uuid::fromString($paymentId);
        } catch (\InvalidArgumentException $exception) {
            $event->markProcessed();
            $this->entityManager->flush();
            return;
        }

        $payment = $this->payments->findByUuid($uuid);
        if (!$payment) {
            $event->markProcessed();
            $this->entityManager->flush();
            return;
        }

        $statusValue = $payload['status'] ?? null;
        if (is_string($statusValue)) {
            $status = PaymentStatus::tryFrom(strtolower($statusValue));
            if ($status) {
                $previousStatus = $payment->getStatus();
                $updated = $payment->updateStatus($status);
                if (!$updated && $previousStatus !== $status) {
                    $this->logger->warning('Ignored invalid payment status transition from webhook.', [
                        'eventId' => $event->getId()->toRfc4122(),
                        'paymentId' => $payment->getId()->toRfc4122(),
                        'fromStatus' => $previousStatus->value,
                        'toStatus' => $status->value,
                    ]);
                }
            }
        }

        $event->markProcessed();
        $this->eventDispatcher->dispatch($payment);
        $this->entityManager->flush();
    }
}
