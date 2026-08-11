<?php

declare(strict_types=1);

namespace Richness\RichPayments\Contracts;

use Illuminate\Http\Request;
use Richness\RichPayments\Data\InquiryResult;
use Richness\RichPayments\Data\PaymentRequest;
use Richness\RichPayments\Data\PaymentSession;
use Richness\RichPayments\Data\WebhookResult;
use Richness\RichPayments\Models\PaymentGateway;

interface PaymentGatewayDriver
{
    public function createSession(PaymentGateway $gateway, PaymentRequest $request): PaymentSession;

    public function handleWebhook(PaymentGateway $gateway, Request $request): WebhookResult;

    public function inquire(PaymentGateway $gateway, string $externalTransactionId): InquiryResult;

    public function checkoutUrl(PaymentGateway $gateway, string $clientSecret): string;
}
