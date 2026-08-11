<?php

declare(strict_types=1);

namespace Richness\RichPayments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property string $code
 * @property string $name
 * @property string $environment
 * @property bool $active
 * @property int $sort_order
 * @property array<string, bool>|null $capabilities
 */
final class PaymentGateway extends Model
{
    protected $table = 'rich_payment_gateways';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'capabilities' => 'array',
        ];
    }

    /**
     * @return HasMany<PaymentMethod, $this>
     */
    public function methods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class, 'gateway_id');
    }

    /**
     * @return HasMany<PaymentCredential, $this>
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(PaymentCredential::class, 'gateway_id');
    }
}
