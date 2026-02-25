<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Controller;

use App\Payments\Application\Message\WebhookEventMessage;
use App\Payments\Domain\Provider;
use App\Payments\Domain\WebhookEvent;
use App\Payments\Infrastructure\HmacSignatureVerifier;
use App\Payments\Infrastructure\Repository\WebhookEventRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final readonly class WebhookController
{
    public function __construct(
        private HmacSignatureVerifier $signatureVerifier,
        private WebhookEventRepository $events,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus,
    ) {
    }

    #[Route('/api/webhooks/{provider}', name: 'webhook_receive', methods: ['POST'])]
    public function receive(string $provider, Request $request): JsonResponse
    {
        $rawPayload = (string) $request->getContent();
        $payload = json_decode($rawPayload, true);
        if (!is_array($payload)) {
            return $this->error('Invalid JSON payload.', 400);
        }

        try {
            $providerEnum = Provider::fromString($provider);
        } catch (\InvalidArgumentException $exception) {
            return $this->error('Unsupported provider.', 400);
        }

        $signatureHeader = $request->headers->get('X-Signature');
        $signatureValid = $this->signatureVerifier->verify($providerEnum->value, $rawPayload, $signatureHeader);

        $externalId = null;
        if (isset($payload['externalId']) && is_string($payload['externalId'])) {
            $externalId = $payload['externalId'];
        } elseif (isset($payload['id']) && is_string($payload['id'])) {
            $externalId = $payload['id'];
        }

        $event = new WebhookEvent(
            Uuid::v4(),
            $providerEnum,
            $payload,
            $rawPayload,
            $signatureHeader,
            $signatureValid,
            $externalId,
        );
        if (!$signatureValid) {
            $event->markProcessed();
        }

        try {
            $this->events->save($event);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            if ($externalId === null) {
                throw $exception;
            }

            $this->entityManager->clear();
            $existingEvent = $this->events->findByProviderAndExternalId($providerEnum, $externalId);
            if ($existingEvent === null) {
                throw $exception;
            }

            return new JsonResponse($this->eventResponse($existingEvent), 202);
        }

        if ($signatureValid) {
            $this->bus->dispatch(new WebhookEventMessage($event->getId()));
        }

        return new JsonResponse($this->eventResponse($event), 202);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['error' => $message], $status);
    }

    private function eventResponse(WebhookEvent $event): array
    {
        return [
            'id' => $event->getId()->toRfc4122(),
            'signatureValid' => $event->isSignatureValid(),
            'receivedAt' => $event->getReceivedAt()->format(DATE_ATOM),
        ];
    }
}
