<?php

declare(strict_types=1);

namespace Richness\RichPayments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Richness\RichPayments\Data\PaymentRequest;
use Richness\RichPayments\Data\PaymentSession;
use Richness\RichPayments\Enums\PaymentStatus;
use Richness\RichPayments\Gateways\GatewayManager;
use Richness\RichPayments\Models\PaymentAttempt;
use Richness\RichPayments\Models\PaymentGateway;
use Richness\RichPayments\Support\PublicId;

final class RichPayments
{
    public function __construct(private readonly GatewayManager $manager) {}

    public function start(PaymentGateway $gateway, PaymentRequest $request, ?Model $payable = null): PaymentSession
    {
        return DB::transaction(function () use ($gateway, $request, $payable): PaymentSession {
            $attempt = PaymentAttempt::query()->create([
                'public_id' => PublicId::generate(),
                'payable_type' => $payable?->getMorphClass(),
                'payable_id' => $payable?->getKey(),
                'amount_minor' => $request->amountMinor,
                'currency' => $request->currency,
                'gateway_code' => $gateway->code,
                'method_code' => $request->methodCode,
                'status' => PaymentStatus::Initiated,
                'merchant_reference' => $request->merchantReference,
                'customer_snapshot' => $request->customer,
                'metadata' => $request->metadata,
            ]);

            $session = $this->manager->driver($gateway)->createSession($gateway, $request);

            $attempt->update([
                'status' => $session->status,
                'external_reference' => $session->externalReference,
                'checkout_url' => $session->checkoutUrl,
            ]);

            return $session;
        });
    }
}
