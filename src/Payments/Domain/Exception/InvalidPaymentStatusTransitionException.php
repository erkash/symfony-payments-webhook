<?php

declare(strict_types=1);

namespace App\Payments\Domain\Exception;

use App\Payments\Domain\PaymentStatus;
use Symfony\Component\Uid\Uuid;

final class InvalidPaymentStatusTransitionException extends \DomainException
{
    public function __construct(Uuid $paymentId, PaymentStatus $from, PaymentStatus $to)
    {
        parent::__construct(sprintf(
            'Cannot transition payment "%s" from status "%s" to "%s".',
            $paymentId->toRfc4122(),
            $from->value,
            $to->value,
        ));
    }
}
