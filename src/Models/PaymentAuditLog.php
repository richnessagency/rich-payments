<?php

declare(strict_types=1);

namespace Richness\RichPayments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read int $id
 * @property int|null $gateway_id
 * @property string|null $actor_type
 * @property int|string|null $actor_id
 * @property string $action
 * @property string|null $subject_type
 * @property int|string|null $subject_id
 * @property array<string, mixed>|null $changes
 * @property string|null $ip_address
 */
final class PaymentAuditLog extends Model
{
    protected $table = 'rich_payment_audit_logs';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    /**
     * @return BelongsTo<PaymentGateway, $this>
     */
    public function gateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'gateway_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}
