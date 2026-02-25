<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Payments\Domain\ValueObject\Currency;
use App\Payments\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testCurrencyNormalizesToUppercase(): void
    {
        $currency = new Currency('usd');
        self::assertSame('USD', $currency->code);
    }

    public function testCurrencyRejectsInvalidCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Currency('US');
    }

    public function testCurrencyRejectsNumericCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Currency('123');
    }

    public function testMoneyStoresAmountAndCurrency(): void
    {
        $money = new Money(1500, new Currency('USD'));
        self::assertSame(1500, $money->getAmount());
        self::assertSame('USD', $money->getCurrency()->code);
    }

    public function testMoneyRejectsZeroAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Money(0, new Currency('USD'));
    }

    public function testMoneyRejectsNegativeAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Money(-100, new Currency('USD'));
    }

    public function testMoneyEquality(): void
    {
        $a = new Money(1500, new Currency('USD'));
        $b = new Money(1500, new Currency('USD'));
        $c = new Money(1500, new Currency('EUR'));

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }
}
