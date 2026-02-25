<?php

declare(strict_types=1);

namespace App\Payments\Domain;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'webhook_events')]
#[ORM\UniqueConstraint(name: 'uniq_webhook_provider_external_id', fields: ['provider', 'externalId'])]
#[ORM\Index(fields: ['externalId'], name: 'idx_webhook_external_id')]
class WebhookEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 32, enumType: Provider::class)]
    private Provider $provider;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    private ?string $externalId;

    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column(name: 'raw_payload', type: 'text')]
    private string $rawPayload;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $signature;

    #[ORM\Column(type: 'boolean')]
    private bool $signatureValid;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $receivedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    public function __construct(
        Uuid $id,
        Provider $provider,
        array $payload,
        string $rawPayload,
        ?string $signature,
        bool $signatureValid,
        ?string $externalId = null
    ) {
        $this->id = $id;
        $this->provider = $provider;
        $this->payload = $payload;
        $this->rawPayload = $rawPayload;
        $this->signature = $signature;
        $this->signatureValid = $signatureValid;
        $this->externalId = $externalId;
        $this->receivedAt = new DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getProvider(): Provider
    {
        return $this->provider;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getRawPayload(): string
    {
        return $this->rawPayload;
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function isSignatureValid(): bool
    {
        return $this->signatureValid;
    }

    public function getReceivedAt(): DateTimeImmutable
    {
        return $this->receivedAt;
    }

    public function getProcessedAt(): ?DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function isProcessed(): bool
    {
        return $this->processedAt !== null;
    }

	public function markProcessed(): void
	{
		if ($this->processedAt !== null) {
			return;
		}

		$this->processedAt = new DateTimeImmutable();
	}
}
