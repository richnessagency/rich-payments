<?php

declare(strict_types=1);

namespace Richness\RichPayments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property string $gateway_code
 * @property string|null $event_id
 * @property string $payload_hash
 * @property bool $verified
 * @property Carbon $received_at
 * @property Carbon|null $processed_at
 * @property string|null $failure_reason
 * @property array<string, mixed>|null $payload_snapshot
 */
final class PaymentWebhookEvent extends Model
{
    protected $table = 'rich_payment_webhook_events';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
            'payload_snapshot' => 'array',
        ];
    }
}
