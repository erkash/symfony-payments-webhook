<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Presenter;

use App\Payments\Domain\Payment;

final class PaymentPresenter
{
    public function present(Payment $payment): array
    {
        return [
            'id' => $payment->getId()->toRfc4122(),
            'amount' => $payment->getAmount(),
            'currency' => $payment->getCurrency(),
            'status' => $payment->getStatus()->value,
            'createdAt' => $payment->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $payment->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
