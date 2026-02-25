<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Payments\Domain\Payment;
use App\Payments\Domain\PaymentStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class PaymentTest extends TestCase
{
    public function testPaymentStartsPendingAndAppliesValidTransitions(): void
    {
        $payment = new Payment(Uuid::v4(), 1500, 'usd');

        self::assertSame(PaymentStatus::Pending, $payment->getStatus());

        $updated = $payment->updateStatus(PaymentStatus::Authorized);
        self::assertTrue($updated);
        self::assertSame(PaymentStatus::Authorized, $payment->getStatus());

        $updated = $payment->updateStatus(PaymentStatus::Succeeded);
        self::assertTrue($updated);
        self::assertSame(PaymentStatus::Succeeded, $payment->getStatus());
    }

    public function testPaymentIgnoresInvalidTransitions(): void
    {
        $payment = new Payment(Uuid::v4(), 1500, 'usd');

        $updated = $payment->updateStatus(PaymentStatus::Succeeded);
        self::assertFalse($updated);
        self::assertSame(PaymentStatus::Pending, $payment->getStatus());

        $payment->updateStatus(PaymentStatus::Authorized);
        self::assertSame(PaymentStatus::Authorized, $payment->getStatus());

        $updated = $payment->updateStatus(PaymentStatus::Pending);
        self::assertFalse($updated);
        self::assertSame(PaymentStatus::Authorized, $payment->getStatus());

        $payment->updateStatus(PaymentStatus::Failed);
        self::assertSame(PaymentStatus::Failed, $payment->getStatus());

        $updated = $payment->updateStatus(PaymentStatus::Succeeded);
        self::assertFalse($updated);
        self::assertSame(PaymentStatus::Failed, $payment->getStatus());
    }
}
