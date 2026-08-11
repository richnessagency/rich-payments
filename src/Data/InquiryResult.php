<?php

declare(strict_types=1);

namespace Richness\RichPayments\Data;

final readonly class InquiryResult
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public bool $found,
        public bool $success,
        public bool $pending,
        public string $status,
        public ?string $externalTransactionId,
        public ?string $merchantReference,
        public ?int $amountMinor,
        public ?string $currency,
        public array $payload = [],
        public ?string $failureReason = null,
    ) {}
}
