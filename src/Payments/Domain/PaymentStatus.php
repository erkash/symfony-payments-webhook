<?php

declare(strict_types=1);

namespace App\Payments\Domain;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Authorized = 'authorized';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
