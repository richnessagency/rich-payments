<?php

declare(strict_types=1);

namespace Richness\RichPayments\Data;

final readonly class PaymentSession
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $gatewayCode,
        public string $status,
        public ?string $externalReference,
        public ?string $checkoutUrl,
        public array $payload = [],
    ) {}
}
