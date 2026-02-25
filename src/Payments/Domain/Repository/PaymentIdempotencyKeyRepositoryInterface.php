<?php

declare(strict_types=1);

namespace App\Payments\Domain\Repository;

use App\Payments\Domain\PaymentIdempotencyKey;

interface PaymentIdempotencyKeyRepositoryInterface
{
    public function save(PaymentIdempotencyKey $key): void;

    public function findByOperationAndKey(string $operation, string $idempotencyKey): ?PaymentIdempotencyKey;
}
