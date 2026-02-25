<?php

declare(strict_types=1);

namespace App\Payments\Application\Result;

use App\Payments\Domain\Payment;

final readonly class CreatePaymentResult
{
    public function __construct(
        public Payment $payment,
        public bool $replayed,
    ) {
    }
}
