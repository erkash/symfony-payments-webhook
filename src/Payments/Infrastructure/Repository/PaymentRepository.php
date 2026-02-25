<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Repository;

use App\Payments\Domain\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/** @extends ServiceEntityRepository<Payment> */
final class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function save(Payment $payment): void
    {
        $this->_em->persist($payment);
    }

    public function findByUuid(Uuid $id): ?Payment
    {
        return $this->find($id);
    }
}
