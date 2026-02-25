<?php

declare(strict_types=1);

namespace App\Payments\Domain\ValueObject;

final readonly class Currency
{
    public readonly string $code;

    public function __construct(string $code)
    {
        $upper = strtoupper($code);

        if (!preg_match('/^[A-Z]{3}$/', $upper)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid currency code "%s". Must be a 3-letter ISO 4217 code.', $code)
            );
        }

        $this->code = $upper;
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
