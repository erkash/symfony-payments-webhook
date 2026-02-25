<?php

declare(strict_types=1);

namespace App\Payments\Domain\Repository;

use App\Payments\Domain\Provider;
use App\Payments\Domain\WebhookEvent;
use Symfony\Component\Uid\Uuid;

interface WebhookEventRepositoryInterface
{
    public function save(WebhookEvent $event): void;

    public function findByUuid(Uuid $id): ?WebhookEvent;

    public function findByProviderAndExternalId(Provider $provider, string $externalId): ?WebhookEvent;
}
