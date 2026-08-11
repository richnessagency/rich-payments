<?php

declare(strict_types=1);

namespace Richness\RichPayments\Security;

use Illuminate\Support\Facades\Crypt;
use Richness\RichPayments\Models\PaymentCredential;
use Richness\RichPayments\Models\PaymentGateway;

final class CredentialVault
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function put(PaymentGateway $gateway, string $environment, string $key, ?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $credential = PaymentCredential::query()->updateOrCreate(
            [
                'gateway_id' => $gateway->id,
                'environment' => $environment,
                'key_name' => $key,
            ],
            [
                'encrypted_value' => Crypt::encryptString($value),
                'masked_preview' => $this->mask($value),
                'last_rotated_at' => now(),
            ],
        );

        $this->audit->record($gateway, 'credential.rotated', PaymentCredential::class, $credential->id, [
            'key_name' => $key,
            'environment' => $environment,
        ]);
    }

    public function get(PaymentGateway $gateway, string $key, ?string $environment = null): ?string
    {
        $credential = PaymentCredential::query()
            ->where('gateway_id', $gateway->id)
            ->where('environment', $environment ?? $gateway->environment)
            ->where('key_name', $key)
            ->first();

        if (! $credential?->encrypted_value) {
            return null;
        }

        return Crypt::decryptString($credential->encrypted_value);
    }

    public function mask(string $value): string
    {
        $tail = mb_substr($value, -4);

        return str_repeat('•', max(8, mb_strlen($value) - 4)).$tail;
    }
}
