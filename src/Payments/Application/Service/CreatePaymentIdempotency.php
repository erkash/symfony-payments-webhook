<?php

declare(strict_types=1);

namespace App\Payments\Application\Service;

use App\Payments\Application\Dto\CreatePaymentRequest;
use App\Payments\Application\Result\CreatePaymentResult;
use App\Payments\Domain\Payment;
use App\Payments\Domain\PaymentIdempotencyKey;
use App\Payments\Infrastructure\Repository\PaymentIdempotencyKeyRepository;
use App\Payments\Infrastructure\Repository\PaymentRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class CreatePaymentIdempotency
{
    public const OPERATION_CREATE_PAYMENT = 'create_payment';

    public function __construct(
        private PaymentRepository $payments,
        private PaymentIdempotencyKeyRepository $idempotencyKeys,
        private EntityManagerInterface $entityManager,
        private DomainEventDispatcher $eventDispatcher,
    ) {
    }

    public function create(CreatePaymentRequest $dto, ?string $idempotencyKey): CreatePaymentResult
    {
        $normalizedIdempotencyKey = $this->normalizeIdempotencyKey($idempotencyKey);
        if ($normalizedIdempotencyKey === null) {
            return $this->createWithoutIdempotency($dto);
        }

        return $this->createWithIdempotency($dto, $normalizedIdempotencyKey);
    }

    private function createWithoutIdempotency(CreatePaymentRequest $dto): CreatePaymentResult
    {
        $payment = new Payment(Uuid::v4(), $dto->amount, $dto->currency);
        $this->payments->save($payment);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch($payment);

        return new CreatePaymentResult($payment, false);
    }

    private function createWithIdempotency(CreatePaymentRequest $dto, string $idempotencyKey): CreatePaymentResult
    {
        $this->entityManager->beginTransaction();

        try {
            $payment = new Payment(Uuid::v4(), $dto->amount, $dto->currency);
            $this->payments->save($payment);

            $key = new PaymentIdempotencyKey(
                Uuid::v4(),
                self::OPERATION_CREATE_PAYMENT,
                $idempotencyKey,
                $payment,
            );
            $this->idempotencyKeys->save($key);

            $this->entityManager->flush();
            $this->eventDispatcher->dispatch($payment);
            $this->entityManager->commit();

            return new CreatePaymentResult($payment, false);
        } catch (UniqueConstraintViolationException $exception) {
            $this->rollbackIfActive();
            $this->entityManager->clear();

            $existingKey = $this->idempotencyKeys->findByOperationAndKey(self::OPERATION_CREATE_PAYMENT, $idempotencyKey);
            if ($existingKey === null) {
                throw $exception;
            }

            return new CreatePaymentResult($existingKey->getPayment(), true);
        } catch (\Throwable $exception) {
            $this->rollbackIfActive();
            throw $exception;
        }
    }

    private function normalizeIdempotencyKey(?string $idempotencyKey): ?string
    {
        if ($idempotencyKey === null) {
            return null;
        }

        $trimmed = trim($idempotencyKey);
        return $trimmed === '' ? null : $trimmed;
    }

    private function rollbackIfActive(): void
    {
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }
    }
}
