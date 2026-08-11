<?php

declare(strict_types=1);

namespace Richness\RichPayments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Richness\RichPayments\Security\EncryptedAttribute;

/**
 * @property-read int $id
 * @property int $gateway_id
 * @property string $code
 * @property string $display_name_ar
 * @property string|null $display_name_en
 * @property string|null $integration_identifier
 * @property bool $active
 * @property int $sort_order
 * @property array{percent?: string, fixed_minor?: int}|null $fees_config
 */
final class PaymentMethod extends Model
{
    use EncryptedAttribute;

    protected $table = 'rich_payment_methods';

    protected $guarded = [];

    /** @var array<int, string> */
    protected array $encryptedAttributes = [
        'integration_identifier',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'fees_config' => 'array',
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
