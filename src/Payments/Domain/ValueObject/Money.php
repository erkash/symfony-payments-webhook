<?php

declare(strict_types=1);

namespace App\Payments\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class Money
{
    #[ORM\Column(type: 'integer')]
    private int $amount;

    #[ORM\Column(name: 'currency', type: 'string', length: 3)]
    private string $currencyCode;

    public function __construct(int $amount, Currency $currency)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException(
                sprintf('Amount must be a positive integer, %d given.', $amount)
            );
        }

        $this->amount = $amount;
        $this->currencyCode = $currency->code;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): Currency
    {
        return new Currency($this->currencyCode);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount
            && $this->currencyCode === $other->currencyCode;
    }
}
