<?php

declare(strict_types=1);

namespace App\Payments\Domain;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'payment_idempotency_keys')]
#[ORM\UniqueConstraint(name: 'uniq_payment_idempotency_operation_key', fields: ['operation', 'idempotencyKey'])]
#[ORM\Index(name: 'idx_payment_idempotency_payment_id', fields: ['payment'])]
final class PaymentIdempotencyKey
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 64)]
    private string $operation;

    #[ORM\Column(name: 'idempotency_key', type: 'string', length: 128)]
    private string $idempotencyKey;

    #[ORM\ManyToOne(targetEntity: Payment::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'payment_id', referencedColumnName: 'id', nullable: false)]
    private Payment $payment;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(Uuid $id, string $operation, string $idempotencyKey, Payment $payment)
    {
        $this->id = $id;
        $this->operation = $operation;
        $this->idempotencyKey = $idempotencyKey;
        $this->payment = $payment;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function getIdempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function getPayment(): Payment
    {
        return $this->payment;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
