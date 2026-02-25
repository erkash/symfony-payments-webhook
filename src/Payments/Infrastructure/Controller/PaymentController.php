<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Controller;

use App\Payments\Application\Dto\CreatePaymentRequest;
use App\Payments\Application\Service\CreatePaymentIdempotency;
use App\Payments\Domain\Repository\PaymentRepositoryInterface;
use App\Payments\Infrastructure\Presenter\PaymentPresenter;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final readonly class PaymentController
{
    public function __construct(
        private PaymentRepositoryInterface $payments,
        private CreatePaymentIdempotency $createPayment,
        private PaymentPresenter $presenter,
    ) {
    }

    #[Route('/api/payments', name: 'create_payment', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreatePaymentRequest $dto, Request $request): JsonResponse
    {
        $idempotencyKey = $request->headers->get('Idempotency-Key');
        $result = $this->createPayment->create($dto, $idempotencyKey);

        $statusCode = $result->replayed ? 200 : 201;
        return new JsonResponse(
            $this->presenter->present($result->payment),
            $statusCode,
            ['Idempotency-Replayed' => $result->replayed ? 'true' : 'false'],
        );
    }

    #[Route('/api/payments/{id}', name: 'get_payment', methods: ['GET'])]
    public function getById(string $id): JsonResponse
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (InvalidArgumentException $exception) {
            return $this->error('Invalid payment id.', 400);
        }

        $payment = $this->payments->findByUuid($uuid);
        if (!$payment) {
            return $this->error('Payment not found.', 404);
        }

        return new JsonResponse($this->presenter->present($payment));
    }

    private function error(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['error' => $message], $status);
    }
}
