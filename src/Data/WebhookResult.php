<?php

declare(strict_types=1);

namespace Richness\RichPayments\Data;

final readonly class WebhookResult
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public bool $verified,
        public bool $success,
        public string $status,
        public ?string $merchantReference,
        public ?string $externalTransactionId,
        public ?int $paidAmountMinor,
        public ?string $currency,
        public array $payload,
        public ?string $failureReason = null,
        public string $callbackType = 'transaction',
    ) {}
}
