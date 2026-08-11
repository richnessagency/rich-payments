<?php

declare(strict_types=1);

namespace Richness\RichPayments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property int $gateway_id
 * @property string $environment
 * @property string $key_name
 * @property string|null $encrypted_value
 * @property string|null $masked_preview
 * @property Carbon|null $last_rotated_at
 */
final class PaymentCredential extends Model
{
    protected $table = 'rich_payment_credentials';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'last_rotated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PaymentGateway, $this>
     */
    public function gateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'gateway_id');
    }
}
