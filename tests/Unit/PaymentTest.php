<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Payments\Domain\Exception\InvalidPaymentStatusTransitionException;
use App\Payments\Domain\Payment;
use App\Payments\Domain\PaymentStatus;
use App\Payments\Domain\ValueObject\Currency;
use App\Payments\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class PaymentTest extends TestCase
{
    public function testPaymentStartsPendingAndAppliesValidTransitions(): void
    {
        $payment = new Payment(Uuid::v4(), new Money(1500, new Currency('USD')));

        self::assertSame(PaymentStatus::Pending, $payment->getStatus());

        $updated = $payment->updateStatus(PaymentStatus::Authorized);
        self::assertTrue($updated);
        self::assertSame(PaymentStatus::Authorized, $payment->getStatus());

        $updated = $payment->updateStatus(PaymentStatus::Succeeded);
        self::assertTrue($updated);
        self::assertSame(PaymentStatus::Succeeded, $payment->getStatus());
    }

    public function testSameStatusIsIdempotent(): void
    {
        $payment = new Payment(Uuid::v4(), new Money(1500, new Currency('USD')));

        $updated = $payment->updateStatus(PaymentStatus::Pending);
        self::assertFalse($updated);
        self::assertSame(PaymentStatus::Pending, $payment->getStatus());
    }

    /**
     * @dataProvider invalidTransitionProvider
     */
    public function testInvalidTransitionThrowsException(PaymentStatus $from, PaymentStatus $to): void
    {
        $payment = new Payment(Uuid::v4(), new Money(1500, new Currency('USD')));
        if ($from !== PaymentStatus::Pending) {
            $payment->updateStatus(PaymentStatus::Authorized);
        }
        if ($from === PaymentStatus::Failed) {
            $payment->updateStatus(PaymentStatus::Failed);
        }

        $this->expectException(InvalidPaymentStatusTransitionException::class);
        $payment->updateStatus($to);
    }

    public static function invalidTransitionProvider(): array
    {
        return [
            'Pending → Succeeded' => [PaymentStatus::Pending, PaymentStatus::Succeeded],
            'Authorized → Pending' => [PaymentStatus::Authorized, PaymentStatus::Pending],
            'Failed → Succeeded' => [PaymentStatus::Failed, PaymentStatus::Succeeded],
        ];
    }
}
