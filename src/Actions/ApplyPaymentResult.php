<?php

declare(strict_types=1);

namespace Richness\RichPayments\Actions;

use Illuminate\Support\Facades\DB;
use Richness\RichPayments\Data\InquiryResult;
use Richness\RichPayments\Data\RefundResult;
use Richness\RichPayments\Data\WebhookResult;
use Richness\RichPayments\Enums\PaymentStatus;
use Richness\RichPayments\Events\PaymentFailed;
use Richness\RichPayments\Events\PaymentPaid;
use Richness\RichPayments\Events\PaymentPending;
use Richness\RichPayments\Events\PaymentRefunded;
use Richness\RichPayments\Models\PaymentAttempt;
use Richness\RichPayments\Models\PaymentGateway;
use Richness\RichPayments\Models\PaymentTransaction;

final class ApplyPaymentResult
{
    public function fromWebhook(PaymentGateway $gateway, WebhookResult $result): PaymentTransaction
    {
        return $this->apply(
            gateway: $gateway,
            status: $result->status,
            success: $result->success,
            merchantReference: $result->merchantReference,
            externalTransactionId: $result->externalTransactionId,
            amountMinor: $result->paidAmountMinor,
            currency: $result->currency,
            payload: $result->payload,
            shouldUpdateAttempt: $result->verified,
        );
    }

    public function fromInquiry(PaymentGateway $gateway, InquiryResult $result): PaymentTransaction
    {
        return $this->apply(
            gateway: $gateway,
            status: $result->status,
            success: $result->success,
            merchantReference: $result->merchantReference,
            externalTransactionId: $result->externalTransactionId,
            amountMinor: $result->amountMinor,
            currency: $result->currency,
            payload: $result->payload,
            shouldUpdateAttempt: $result->found,
        );
    }

    public function fromRefund(PaymentGateway $gateway, PaymentAttempt $attempt, RefundResult $result): ?PaymentTransaction
    {
        if (! $result->success) {
            return null;
        }

        return DB::transaction(function () use ($gateway, $attempt, $result): PaymentTransaction {
            $transaction = PaymentTransaction::query()->create([
                'attempt_id' => $attempt->id,
                'gateway_code' => $gateway->code,
                'external_transaction_id' => $result->externalTransactionId,
                'status' => match ($result->action) {
                    'voided' => PaymentStatus::Cancelled->value,
                    'captured' => 'captured',
                    default => PaymentStatus::Refunded->value,
                },
                'success' => true,
                'paid_amount_minor' => $result->amountMinor,
                'currency' => $attempt->currency,
                'raw_payload_snapshot' => $this->redacted($result->payload),
            ]);

            if ($result->action !== 'captured') {
                $attempt->update([
                    'status' => $result->action === 'voided' ? PaymentStatus::Cancelled : PaymentStatus::Refunded,
                ]);

                event(new PaymentRefunded($attempt->refresh(), $transaction));
            }

            return $transaction;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function apply(
        PaymentGateway $gateway,
        string $status,
        bool $success,
        ?string $merchantReference,
        ?string $externalTransactionId,
        ?int $amountMinor,
        ?string $currency,
        array $payload,
        bool $shouldUpdateAttempt,
    ): PaymentTransaction {
        return DB::transaction(function () use ($gateway, $status, $success, $merchantReference, $externalTransactionId, $amountMinor, $currency, $payload, $shouldUpdateAttempt): PaymentTransaction {
            $attempt = $merchantReference
                ? PaymentAttempt::query()->where('merchant_reference', $merchantReference)->lockForUpdate()->first()
                : null;

            $transaction = PaymentTransaction::query()->create([
                'attempt_id' => $attempt?->id,
                'gateway_code' => $gateway->code,
                'external_transaction_id' => $externalTransactionId,
                'status' => $status,
                'success' => $success,
                'paid_amount_minor' => $amountMinor,
                'currency' => $currency,
                'raw_payload_snapshot' => $this->redacted($payload),
            ]);

            if ($attempt && $shouldUpdateAttempt) {
                $attempt->update([
                    'status' => $status,
                    'paid_at' => $status === PaymentStatus::Paid->value ? now() : $attempt->paid_at,
                ]);

                $this->dispatchStatusEvent($attempt->refresh(), $transaction);
            }

            return $transaction;
        });
    }

    private function dispatchStatusEvent(PaymentAttempt $attempt, PaymentTransaction $transaction): void
    {
        match ($attempt->status) {
            PaymentStatus::Paid => event(new PaymentPaid($attempt, $transaction)),
            PaymentStatus::Failed => event(new PaymentFailed($attempt, $transaction)),
            PaymentStatus::Pending => event(new PaymentPending($attempt, $transaction)),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function redacted(array $payload): array
    {
        foreach (['secret_key', 'public_key', 'api_key', 'hmac_secret', 'token'] as $key) {
            if (array_key_exists($key, $payload)) {
                $payload[$key] = '[redacted]';
            }
        }

        return $payload;
    }
}
