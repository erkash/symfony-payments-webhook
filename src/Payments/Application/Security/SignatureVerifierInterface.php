<?php

declare(strict_types=1);

namespace App\Payments\Application\Security;

interface SignatureVerifierInterface
{
    public function verify(string $provider, string $payload, ?string $signatureHeader): bool;
}
