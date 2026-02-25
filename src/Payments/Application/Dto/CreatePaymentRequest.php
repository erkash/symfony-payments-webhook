<?php

declare(strict_types=1);

namespace App\Payments\Application\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreatePaymentRequest
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Assert\Type('integer')]
    public int $amount;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 3)]
    #[Assert\Regex(pattern: '/^[A-Za-z]{3}$/', message: 'Currency must be a 3-letter ISO code.')]
    public string $currency;
}
