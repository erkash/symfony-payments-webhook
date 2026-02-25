<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure;

use App\Payments\Application\Security\SignatureVerifierInterface;

final class HmacSignatureVerifier implements SignatureVerifierInterface
{
    /** @var array<string, string> */
    private array $providerSecrets;

    /**
     * @param array<string, string> $providerSecrets
     */
    public function __construct(array $providerSecrets)
    {
        $this->providerSecrets = $providerSecrets;
    }

    public function verify(string $provider, string $payload, ?string $signatureHeader): bool
    {
        if (!$signatureHeader) {
            return false;
        }

        $providerKey = strtolower($provider);
        if (!array_key_exists($providerKey, $this->providerSecrets)) {
            return false;
        }

        $secret = $this->providerSecrets[$providerKey];
        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signatureHeader);
    }
}
