<?php

declare(strict_types=1);

namespace App\Payments\Application\Message;

use Symfony\Component\Uid\Uuid;

final readonly class WebhookEventMessage
{
    public function __construct(public Uuid $eventId)
    {
    }
}
