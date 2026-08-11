<?php

declare(strict_types=1);

namespace Richness\RichPayments\Data;

final readonly class PaymentRequest
{
    /**
     * @param  array<int, array{name: string, amount_minor: int, quantity?: int}>  $items
     * @param  array<string, mixed>  $customer
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public int $amountMinor,
        public string $currency,
        public string $merchantReference,
        public ?string $methodCode = null,
        public array $items = [],
        public array $customer = [],
        public array $metadata = [],
        public ?string $notificationUrl = null,
        public ?string $redirectionUrl = null,
    ) {}
}
