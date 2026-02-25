<?php

declare(strict_types=1);

namespace App\Payments\Domain;

use App\Payments\Domain\Event\PaymentCreated;
use App\Payments\Domain\Event\PaymentStatusChanged;
use App\Payments\Domain\Event\RecordsEventsInterface;
use App\Payments\Domain\Event\RecordsEventsTrait;
use App\Payments\Domain\ValueObject\Money;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'payments')]
class Payment implements RecordsEventsInterface
{
    use RecordsEventsTrait;
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Embedded(class: Money::class, columnPrefix: false)]
    private Money $money;

    #[ORM\Column(type: 'string', length: 32, enumType: PaymentStatus::class)]
    private PaymentStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(Uuid $id, Money $money)
    {
        $this->id = $id;
        $this->money = $money;
        $this->status = PaymentStatus::Pending;
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;

        $this->recordEvent(new PaymentCreated($id, $money, $this->status));
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getMoney(): Money
    {
        return $this->money;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updateStatus(PaymentStatus $status): bool
    {
        if ($this->status === $status) {
            return false;
        }

        if (!$this->canTransitionTo($status)) {
            return false;
        }

        $previousStatus = $this->status;
        $this->status = $status;
        $this->touch();

        $this->recordEvent(new PaymentStatusChanged($this->id, $previousStatus, $status));

        return true;
    }

    private function canTransitionTo(PaymentStatus $newStatus): bool
    {
        return match ($this->status) {
            PaymentStatus::Pending => in_array($newStatus, [PaymentStatus::Authorized, PaymentStatus::Failed], true),
            PaymentStatus::Authorized => in_array($newStatus, [PaymentStatus::Succeeded, PaymentStatus::Failed], true),
            PaymentStatus::Succeeded, PaymentStatus::Failed => false,
        };
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
