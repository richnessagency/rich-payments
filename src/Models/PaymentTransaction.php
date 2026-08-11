<?php

declare(strict_types=1);

namespace Richness\RichPayments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int|null $attempt_id
 * @property string $gateway_code
 * @property string|null $external_transaction_id
 * @property string $status
 * @property bool $success
 * @property int|null $paid_amount_minor
 * @property string|null $currency
 * @property array<string, mixed>|null $raw_payload_snapshot
 */
final class PaymentTransaction extends Model
{
    protected $table = 'rich_payment_transactions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'raw_payload_snapshot' => 'array',
        ];
    }

    /**
     * @return BelongsTo<PaymentAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class, 'attempt_id');
    }
}
