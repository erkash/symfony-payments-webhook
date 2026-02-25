<?php

declare(strict_types=1);

namespace App\Payments\Domain;

enum Provider: string
{
    case Stripe = 'stripe';
    case Adyen = 'adyen';

    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'stripe' => self::Stripe,
            'adyen' => self::Adyen,
            default => throw new \InvalidArgumentException('Unsupported provider.'),
        };
    }
}
