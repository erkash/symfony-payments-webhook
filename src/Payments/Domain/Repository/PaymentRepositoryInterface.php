<?php

declare(strict_types=1);

namespace App\Payments\Domain\Repository;

use App\Payments\Domain\Payment;
use Symfony\Component\Uid\Uuid;

interface PaymentRepositoryInterface
{
    public function save(Payment $payment): void;

    public function findByUuid(Uuid $id): ?Payment;
}
