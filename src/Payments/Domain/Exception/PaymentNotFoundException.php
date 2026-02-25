<?php

declare(strict_types=1);

namespace App\Payments\Domain\Exception;

use Symfony\Component\Uid\Uuid;

final class PaymentNotFoundException extends \DomainException
{
    public function __construct(Uuid $id)
    {
        parent::__construct(sprintf('Payment with ID "%s" not found.', $id->toRfc4122()));
    }
}
