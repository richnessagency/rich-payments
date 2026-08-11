<?php

declare(strict_types=1);

namespace Richness\RichPayments\Enums;

enum PaymentStatus: string
{
    case Initiated = 'initiated';
    case Redirected = 'redirected';
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';
}
