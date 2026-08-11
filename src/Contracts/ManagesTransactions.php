<?php

declare(strict_types=1);

namespace Richness\RichPayments\Contracts;

use Richness\RichPayments\Data\RefundResult;
use Richness\RichPayments\Models\PaymentGateway;

interface ManagesTransactions
{
    public function refund(PaymentGateway $gateway, string $transactionId, int $amountMinor): RefundResult;

    public function void(PaymentGateway $gateway, string $transactionId): RefundResult;

    public function capture(PaymentGateway $gateway, string $transactionId, int $amountMinor): RefundResult;
}
