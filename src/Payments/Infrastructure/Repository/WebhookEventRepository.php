<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Repository;

use App\Payments\Domain\Provider;
use App\Payments\Domain\WebhookEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/** @extends ServiceEntityRepository<WebhookEvent> */
final class WebhookEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebhookEvent::class);
    }

    public function save(WebhookEvent $event): void
    {
        $this->_em->persist($event);
    }

    public function findByUuid(Uuid $id): ?WebhookEvent
    {
        return $this->find($id);
    }

    public function findByProviderAndExternalId(Provider $provider, string $externalId): ?WebhookEvent
    {
        return $this->findOneBy([
            'provider' => $provider,
            'externalId' => $externalId,
        ]);
    }
}
