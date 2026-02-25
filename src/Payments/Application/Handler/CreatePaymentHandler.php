<?php

declare(strict_types=1);

namespace App\Payments\Application\Handler;

use App\Payments\Application\Dto\CreatePaymentRequest;
use App\Payments\Application\Result\CreatePaymentResult;
use App\Payments\Application\Service\CreatePaymentIdempotency;

final readonly class CreatePaymentHandler
{
    public function __construct(
        private CreatePaymentIdempotency $createPaymentIdempotency,
    ) {
    }

    public function handle(CreatePaymentRequest $dto, ?string $idempotencyKey): CreatePaymentResult
    {
        return $this->createPaymentIdempotency->create($dto, $idempotencyKey);
    }
}
