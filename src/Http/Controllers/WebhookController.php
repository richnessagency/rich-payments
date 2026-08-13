<?php

declare(strict_types=1);

namespace Richness\RichPayments\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Richness\RichPayments\Actions\ApplyPaymentResult;
use Richness\RichPayments\Data\WebhookResult;
use Richness\RichPayments\Events\WebhookRejected;
use Richness\RichPayments\Gateways\GatewayManager;
use Richness\RichPayments\Models\PaymentGateway;
use Richness\RichPayments\Models\PaymentWebhookEvent;

final class WebhookController extends Controller
{
    public function __invoke(Request $request, string $gateway, GatewayManager $manager, ApplyPaymentResult $applyPaymentResult): JsonResponse
    {
        $paymentGateway = PaymentGateway::query()->where('code', $gateway)->firstOrFail();
        $payload = $request->all();
        $event = $this->upsertEvent($gateway, $payload);

        if ($event->processed_at !== null) {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        $result = $manager->driver($paymentGateway)->handleWebhook($paymentGateway, $request);

        $this->applyVerifiedTransaction($paymentGateway, $result, $applyPaymentResult);
        $this->markProcessed($event, $result);

        return response()->json(['ok' => true, 'verified' => $result->verified]);
    }

    /**
     * Handles Paymob's Transaction Response GET callback (flattened query params).
     */
    public function response(Request $request, string $gateway, GatewayManager $manager, ApplyPaymentResult $applyPaymentResult): RedirectResponse
    {
        $paymentGateway = PaymentGateway::query()->where('code', $gateway)->firstOrFail();
        $payload = $request->query();
        $event = $this->upsertEvent($gateway, $payload);

        $result = $manager->driver($paymentGateway)->handleWebhook($paymentGateway, $request);

        $this->applyVerifiedTransaction($paymentGateway, $result, $applyPaymentResult);
        $this->markProcessed($event, $result);

        if ($result->verified) {
            $redirect = $this->verifiedResponseRedirect($result);

            if ($redirect instanceof RedirectResponse) {
                return $redirect;
            }

            return redirect()->route($result->success ? 'rich-payments.success' : 'rich-payments.failed');
        }

        return redirect()->route('rich-payments.pending');
    }

    private function verifiedResponseRedirect(WebhookResult $result): ?RedirectResponse
    {
        $route = config('rich-payments.response_redirect_route');

        if (! is_string($route) || $route === '' || ! is_string($result->merchantReference) || $result->merchantReference === '') {
            return null;
        }

        $parameter = config('rich-payments.response_redirect_parameter', 'order');
        $message = $result->success
            ? 'تم تأكيد الدفع بنجاح.'
            : 'عملية الدفع غير ناجحة. يمكنك المحاولة مرة أخرى من صفحة الطلب.';

        return redirect()
            ->route($route, [(string) $parameter => $result->merchantReference])
            ->with('status', $message);
    }

    private function applyVerifiedTransaction(
        PaymentGateway $paymentGateway,
        WebhookResult $result,
        ApplyPaymentResult $applyPaymentResult,
    ): void {
        if (! $result->verified || $result->callbackType !== 'transaction') {
            return;
        }

        $applyPaymentResult->fromWebhook($paymentGateway, $result);
    }

    private function markProcessed(PaymentWebhookEvent $event, WebhookResult $result): void
    {
        $event->update([
            'verified' => $result->verified,
            'processed_at' => now(),
            'failure_reason' => $result->failureReason,
        ]);

        if (! $result->verified) {
            event(new WebhookRejected($event->refresh()));
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertEvent(string $gateway, array $payload): PaymentWebhookEvent
    {
        $payloadHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        return PaymentWebhookEvent::query()->firstOrCreate(
            ['gateway_code' => $gateway, 'payload_hash' => $payloadHash],
            ['payload_snapshot' => $this->redacted($payload), 'received_at' => now()],
        );
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
