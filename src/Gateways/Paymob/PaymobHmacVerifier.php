<?php

declare(strict_types=1);

namespace Richness\RichPayments\Gateways\Paymob;

final class PaymobHmacVerifier
{
    /**
     * Paymob's documented HMAC field order for transaction callbacks
     * (Transaction Processed POST and Transaction Response GET).
     *
     * @var list<string>
     */
    private const TRANSACTION_PROCESSED_FIELDS = [
        'amount_cents',
        'created_at',
        'currency',
        'error_occured',
        'has_parent_transaction',
        'id',
        'integration_id',
        'is_3d_secure',
        'is_auth',
        'is_capture',
        'is_refunded',
        'is_standalone_payment',
        'is_voided',
        'order.id',
        'owner',
        'pending',
        'source_data.pan',
        'source_data.sub_type',
        'source_data.type',
        'success',
    ];

    /**
     * Paymob's documented HMAC field order for the Card Token callback.
     *
     * @var list<string>
     */
    private const CARD_TOKEN_FIELDS = [
        'card_subtype',
        'created_at',
        'email',
        'id',
        'masked_pan',
        'merchant_id',
        'order_id',
        'token',
    ];

    /**
     * Verifies a Paymob callback signature using the documented field order
     * for the detected callback type.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verify(array $payload, ?string $secret, ?string $providedHmac): bool
    {
        if (! $secret || ! $providedHmac) {
            return false;
        }

        $object = is_array($payload['obj'] ?? null) ? $payload['obj'] : $payload;

        if ($this->isCardTokenCallback($payload, $object)) {
            return $this->verifySignature($object, self::CARD_TOKEN_FIELDS, $secret, $providedHmac);
        }

        return $this->verifySignature($object, self::TRANSACTION_PROCESSED_FIELDS, $secret, $providedHmac);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $object
     */
    private function isCardTokenCallback(array $payload, array $object): bool
    {
        return ($payload['type'] ?? null) === 'TOKEN' || array_key_exists('token', $object);
    }

    /**
     * @param  array<string, mixed>  $object
     * @param  list<string>  $fields
     */
    private function verifySignature(array $object, array $fields, string $secret, string $providedHmac): bool
    {
        $message = implode('', array_map(
            fn (string $field): string => $this->stringify($this->resolve($object, $field)),
            $fields,
        ));

        return hash_equals(hash_hmac('sha512', $message, $secret), $providedHmac);
    }

    /**
     * Resolves a documented field from the callback object, supporting both the
     * nested shape (obj.order.id) and the flattened Transaction Response shape
     * (order_id, source_data_pan) used by Paymob's GET callback.
     *
     * @param  array<string, mixed>  $object
     */
    private function resolve(array $object, string $path): mixed
    {
        $current = $object;

        foreach (explode('.', $path) as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                $flattened = str_replace('.', '_', $path);

                return array_key_exists($flattened, $object) ? $object[$flattened] : null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    private function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return '';
        }

        return is_scalar($value) ? (string) $value : '';
    }
}
