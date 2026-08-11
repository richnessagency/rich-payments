<?php

declare(strict_types=1);

namespace Richness\RichPayments\Events;

use Richness\RichPayments\Models\PaymentAttempt;
use Richness\RichPayments\Models\PaymentTransaction;

final readonly class PaymentFailed
{
    public function __construct(
        public PaymentAttempt $attempt,
        public ?PaymentTransaction $transaction = null,
    ) {}
}
