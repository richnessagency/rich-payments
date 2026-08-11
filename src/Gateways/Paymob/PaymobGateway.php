<?php

declare(strict_types=1);

namespace Richness\RichPayments\Gateways\Paymob;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Richness\RichPayments\Contracts\ManagesTransactions;
use Richness\RichPayments\Contracts\PaymentGatewayDriver;
use Richness\RichPayments\Contracts\SupportsConnectionTest;
use Richness\RichPayments\Data\ConnectionResult;
use Richness\RichPayments\Data\InquiryResult;
use Richness\RichPayments\Data\PaymentRequest;
use Richness\RichPayments\Data\PaymentSession;
use Richness\RichPayments\Data\RefundResult;
use Richness\RichPayments\Data\WebhookResult;
use Richness\RichPayments\Enums\PaymentStatus;
use Richness\RichPayments\Models\PaymentGateway;
use Richness\RichPayments\Security\CredentialVault;
use Throwable;

final class PaymobGateway implements ManagesTransactions, PaymentGatewayDriver, SupportsConnectionTest
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly CredentialVault $vault,
        private readonly PaymobHmacVerifier $hmacVerifier,
    ) {}

    public function createSession(PaymentGateway $gateway, PaymentRequest $request): PaymentSession
    {
        $secretKey = $this->vault->get($gateway, 'secret_key');
        $publicKey = $this->vault->get($gateway, 'public_key');
        $baseUrl = rtrim((string) config('rich-payments.gateways.paymob.base_url'), '/');
        $endpoint = $baseUrl.config('rich-payments.gateways.paymob.intention_endpoint');
        $integrationIds = $this->integrationIds($gateway, $request->methodCode);

        $payload = [
            'amount' => $request->amountMinor,
            'currency' => $request->currency,
            'payment_methods' => $integrationIds,
            'items' => $request->items,
            'billing_data' => $this->billingData($request),
            'special_reference' => $request->merchantReference,
            'notification_url' => $request->notificationUrl,
            'redirection_url' => $request->redirectionUrl,
            'extras' => $request->metadata,
        ];

        $response = $this->http
            ->withHeaders(['Authorization' => "Token {$secretKey}"])
            ->acceptJson()
            ->asJson()
            ->post($endpoint, array_filter($payload, static fn (mixed $value): bool => $value !== null && $value !== []))
            ->throw()
            ->json();

        $clientSecret = is_array($response) ? ($response['client_secret'] ?? null) : null;
        $externalReference = is_array($response) ? (string) ($response['id'] ?? $request->merchantReference) : $request->merchantReference;

        return new PaymentSession(
            gatewayCode: $gateway->code,
            status: PaymentStatus::Redirected->value,
            externalReference: $externalReference,
            checkoutUrl: $clientSecret && $publicKey ? $this->checkoutUrl($gateway, (string) $clientSecret) : null,
            payload: is_array($response) ? $response : [],
        );
    }

    public function handleWebhook(PaymentGateway $gateway, Request $request): WebhookResult
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->all();
        $hmac = $request->query('hmac') ?: $request->input('hmac');
        $secret = $this->vault->get($gateway, 'hmac_secret');
        $verified = $this->hmacVerifier->verify($payload, $secret, is_string($hmac) ? $hmac : null);
        $object = is_array($payload['obj'] ?? null) ? $payload['obj'] : $payload;

        $isCardToken = ($payload['type'] ?? null) === 'TOKEN' || array_key_exists('token', $object);

        if ($isCardToken) {
            return new WebhookResult(
                verified: $verified,
                success: false,
                status: PaymentStatus::Initiated->value,
                merchantReference: $this->stringValue($object['order_id'] ?? null),
                externalTransactionId: null,
                paidAmountMinor: null,
                currency: null,
                payload: $payload,
                failureReason: $verified ? null : 'Invalid Paymob card token HMAC signature.',
                callbackType: 'card_token',
            );
        }

        $success = filter_var($object['success'] ?? false, FILTER_VALIDATE_BOOL);
        $merchantReference = $this->stringValue($object['special_reference'] ?? $object['merchant_order_id'] ?? $object['order']['merchant_order_id'] ?? null);
        $transactionId = $this->stringValue($object['id'] ?? null);
        $amount = $this->intValue($object['amount_cents'] ?? $object['amount'] ?? null);
        $currency = $this->stringValue($object['currency'] ?? null);

        return new WebhookResult(
            verified: $verified,
            success: $success,
            status: $success ? PaymentStatus::Paid->value : PaymentStatus::Failed->value,
            merchantReference: $merchantReference,
            externalTransactionId: $transactionId,
            paidAmountMinor: $amount,
            currency: $currency,
            payload: $payload,
            failureReason: $verified ? null : 'Invalid Paymob HMAC signature.',
        );
    }

    public function inquire(PaymentGateway $gateway, string $externalTransactionId): InquiryResult
    {
        $apiKey = $this->vault->get($gateway, 'api_key');
        $baseUrl = rtrim((string) config('rich-payments.gateways.paymob.base_url'), '/');

        if (! $apiKey) {
            return new InquiryResult(
                found: false,
                success: false,
                pending: false,
                status: PaymentStatus::Failed->value,
                externalTransactionId: $externalTransactionId,
                merchantReference: null,
                amountMinor: null,
                currency: null,
                failureReason: 'Missing Paymob API key.',
            );
        }

        $auth = $this->http
            ->acceptJson()
            ->asJson()
            ->post($baseUrl.'/api/auth/tokens', ['api_key' => $apiKey])
            ->throw()
            ->json();

        $token = is_array($auth) ? ($auth['token'] ?? null) : null;

        if (! is_string($token) || $token === '') {
            return new InquiryResult(
                found: false,
                success: false,
                pending: false,
                status: PaymentStatus::Failed->value,
                externalTransactionId: $externalTransactionId,
                merchantReference: null,
                amountMinor: null,
                currency: null,
                failureReason: 'Paymob auth token was not returned.',
            );
        }

        $payload = $this->http
            ->acceptJson()
            ->get($baseUrl.'/api/acceptance/transactions/'.$externalTransactionId, ['token' => $token])
            ->throw()
            ->json();

        /** @var array<string, mixed> $transaction */
        $transaction = is_array($payload) ? $payload : [];
        $success = filter_var($transaction['success'] ?? false, FILTER_VALIDATE_BOOL);
        $pending = filter_var($transaction['pending'] ?? false, FILTER_VALIDATE_BOOL);

        return new InquiryResult(
            found: $transaction !== [],
            success: $success,
            pending: $pending,
            status: $success ? PaymentStatus::Paid->value : ($pending ? PaymentStatus::Pending->value : PaymentStatus::Failed->value),
            externalTransactionId: $this->stringValue($transaction['id'] ?? $externalTransactionId),
            merchantReference: $this->stringValue($transaction['special_reference'] ?? $transaction['merchant_order_id'] ?? $transaction['order']['merchant_order_id'] ?? null),
            amountMinor: $this->intValue($transaction['amount_cents'] ?? $transaction['amount'] ?? null),
            currency: $this->stringValue($transaction['currency'] ?? null),
            payload: $transaction,
        );
    }

    public function refund(PaymentGateway $gateway, string $transactionId, int $amountMinor): RefundResult
    {
        return $this->transactionAction(
            gateway: $gateway,
            path: '/api/acceptance/void_refund/refund',
            transactionId: $transactionId,
            amountMinor: $amountMinor,
            action: 'refunded',
        );
    }

    public function void(PaymentGateway $gateway, string $transactionId): RefundResult
    {
        return $this->transactionAction(
            gateway: $gateway,
            path: '/api/acceptance/void_refund/void',
            transactionId: $transactionId,
            amountMinor: null,
            action: 'voided',
        );
    }

    public function capture(PaymentGateway $gateway, string $transactionId, int $amountMinor): RefundResult
    {
        return $this->transactionAction(
            gateway: $gateway,
            path: '/api/acceptance/capture',
            transactionId: $transactionId,
            amountMinor: $amountMinor,
            action: 'captured',
        );
    }

    public function testConnection(PaymentGateway $gateway): ConnectionResult
    {
        $secretKey = $this->vault->get($gateway, 'secret_key');
        $publicKey = $this->vault->get($gateway, 'public_key');
        $apiKey = $this->vault->get($gateway, 'api_key');

        $missing = [];

        if (! $secretKey) {
            $missing[] = 'secret_key';
        }

        if (! $publicKey) {
            $missing[] = 'public_key';
        }

        if (! $apiKey) {
            $missing[] = 'api_key';
        }

        if ($missing !== []) {
            return new ConnectionResult(false, 'المفاتيح الناقصة: '.implode(', ', $missing));
        }

        try {
            $baseUrl = rtrim((string) config('rich-payments.gateways.paymob.base_url'), '/');
            $auth = $this->http
                ->acceptJson()
                ->asJson()
                ->post($baseUrl.'/api/auth/tokens', ['api_key' => $apiKey])
                ->throw()
                ->json();

            /** @var array<string, mixed> $payload */
            $payload = is_array($auth) ? $auth : [];
            $token = $payload['token'] ?? null;

            if (! is_string($token) || $token === '') {
                return new ConnectionResult(false, 'فشل المصادقة: لم يتم إرجاع رمز Paymob صالح.', $payload);
            }

            return new ConnectionResult(true, 'الاتصال ناجح: حسابات Paymob متاحة.', $payload);
        } catch (Throwable $exception) {
            return new ConnectionResult(false, 'فشل الاتصال: '.($this->stringValue($exception->getMessage()) ?? 'خطأ غير معروف.'));
        }
    }

    private function transactionAction(
        PaymentGateway $gateway,
        string $path,
        string $transactionId,
        ?int $amountMinor,
        string $action,
    ): RefundResult {
        $secretKey = $this->vault->get($gateway, 'secret_key');

        if (! $secretKey) {
            return new RefundResult(
                success: false,
                action: $action,
                externalTransactionId: $transactionId,
                amountMinor: $amountMinor,
                failureReason: 'Missing Paymob secret key.',
            );
        }

        $body = ['transaction_id' => (int) $transactionId];

        if ($amountMinor !== null) {
            $body['amount_cents'] = $amountMinor;
        }

        try {
            $baseUrl = rtrim((string) config('rich-payments.gateways.paymob.base_url'), '/');
            $response = $this->http
                ->withHeaders(['Authorization' => "Token {$secretKey}"])
                ->acceptJson()
                ->asJson()
                ->post($baseUrl.$path, $body)
                ->throw()
                ->json();

            /** @var array<string, mixed> $payload */
            $payload = is_array($response) ? $response : [];

            return new RefundResult(
                success: true,
                action: $action,
                externalTransactionId: $this->stringValue($payload['id'] ?? $payload['transaction_id'] ?? null) ?? $transactionId,
                amountMinor: $this->intValue($payload['amount_cents'] ?? $payload['amount'] ?? null) ?? $amountMinor,
                merchantReference: $this->stringValue($payload['special_reference'] ?? $payload['merchant_order_id'] ?? null),
                payload: $payload,
            );
        } catch (Throwable $exception) {
            return new RefundResult(
                success: false,
                action: $action,
                externalTransactionId: $transactionId,
                amountMinor: $amountMinor,
                failureReason: $this->safeError($exception),
            );
        }
    }

    public function checkoutUrl(PaymentGateway $gateway, string $clientSecret): string
    {
        $publicKey = $this->vault->get($gateway, 'public_key');
        $baseUrl = rtrim((string) config('rich-payments.gateways.paymob.base_url'), '/');
        $path = (string) config('rich-payments.gateways.paymob.unified_checkout_path');

        return $baseUrl.$path.'?'.http_build_query([
            'publicKey' => $publicKey,
            'clientSecret' => $clientSecret,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function integrationIds(PaymentGateway $gateway, ?string $methodCode): array
    {
        $methods = $gateway->methods()
            ->where('active', true)
            ->when($methodCode, fn ($query) => $query->where('code', $methodCode))
            ->orderBy('sort_order')
            ->get();

        return $methods
            ->map(fn ($method): ?string => $method->integration_identifier ? (string) $method->integration_identifier : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function billingData(PaymentRequest $request): array
    {
        $customer = $request->customer;

        return [
            'first_name' => (string) ($customer['first_name'] ?? $customer['name'] ?? 'Customer'),
            'last_name' => (string) ($customer['last_name'] ?? '.'),
            'email' => (string) ($customer['email'] ?? 'customer@example.com'),
            'phone_number' => (string) ($customer['phone'] ?? $customer['phone_number'] ?? '01000000000'),
        ];
    }

    private function stringValue(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }

    private function safeError(Throwable $exception): string
    {
        $message = $exception->getMessage();

        if ($exception instanceof RequestException) {
            $message = (string) $exception->response->json('message', $message);
        }

        return 'Paymob '.($this->stringValue($message) ?? 'Transaction action failed.');
    }

    private function intValue(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
