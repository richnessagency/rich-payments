<?php

declare(strict_types=1);

namespace Richness\RichPayments\Events;

use Richness\RichPayments\Models\PaymentWebhookEvent;

final readonly class WebhookRejected
{
    public function __construct(public PaymentWebhookEvent $event) {}
}
