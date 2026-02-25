<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Repository;

use App\Payments\Domain\PaymentIdempotencyKey;
use App\Payments\Domain\Repository\PaymentIdempotencyKeyRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PaymentIdempotencyKey> */
final class PaymentIdempotencyKeyRepository extends ServiceEntityRepository implements PaymentIdempotencyKeyRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentIdempotencyKey::class);
    }

    public function save(PaymentIdempotencyKey $idempotencyKey): void
    {
        $this->_em->persist($idempotencyKey);
    }

    public function findByOperationAndKey(string $operation, string $idempotencyKey): ?PaymentIdempotencyKey
    {
        return $this->findOneBy([
            'operation' => $operation,
            'idempotencyKey' => $idempotencyKey,
        ]);
    }
}
