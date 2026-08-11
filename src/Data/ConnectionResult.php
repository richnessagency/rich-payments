<?php

declare(strict_types=1);

namespace Richness\RichPayments\Data;

final readonly class ConnectionResult
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public bool $success,
        public ?string $message = null,
        public array $payload = [],
    ) {}
}
