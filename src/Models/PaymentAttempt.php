<?php

declare(strict_types=1);

namespace Richness\RichPayments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Richness\RichPayments\Enums\PaymentStatus;

/**
 * @property-read int $id
 * @property string $public_id
 * @property string|null $payable_type
 * @property int|string|null $payable_id
 * @property int $amount_minor
 * @property string $currency
 * @property string $gateway_code
 * @property string|null $method_code
 * @property PaymentStatus $status
 * @property string|null $merchant_reference
 * @property string|null $external_reference
 * @property string|null $checkout_url
 * @property array<string, mixed>|null $customer_snapshot
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $paid_at
 */
final class PaymentAttempt extends Model
{
    protected $table = 'rich_payment_attempts';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'customer_snapshot' => 'array',
            'metadata' => 'array',
            'paid_at' => 'datetime',
            'status' => PaymentStatus::class,
        ];
    }

    /**
     * @return HasMany<PaymentTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'attempt_id');
    }
}
