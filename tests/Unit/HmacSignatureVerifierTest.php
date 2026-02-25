<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Payments\Infrastructure\HmacSignatureVerifier;
use PHPUnit\Framework\TestCase;

final class HmacSignatureVerifierTest extends TestCase
{
    public function testValidatesSignature(): void
    {
        $verifier = new HmacSignatureVerifier(['stripe' => 'secret']);
        $payload = '{"id":"evt_1","status":"succeeded"}';
        $signature = hash_hmac('sha256', $payload, 'secret');

        self::assertTrue($verifier->verify('stripe', $payload, $signature));
        self::assertFalse($verifier->verify('stripe', $payload, 'bad'));
    }
}
