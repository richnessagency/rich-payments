<?php

declare(strict_types=1);

namespace Richness\RichPayments\Data;

final readonly class RefundResult
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public bool $success,
        public string $action,
        public ?string $externalTransactionId = null,
        public ?int $amountMinor = null,
        public ?string $merchantReference = null,
        public array $payload = [],
        public ?string $failureReason = null,
    ) {}
}
