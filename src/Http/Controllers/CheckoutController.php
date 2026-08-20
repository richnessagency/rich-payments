<?php

declare(strict_types=1);

namespace Richness\RichPayments\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Richness\RichPayments\Data\PaymentRequest;
use Richness\RichPayments\Enums\PaymentStatus;
use Richness\RichPayments\Models\PaymentAttempt;
use Richness\RichPayments\Models\PaymentGateway;
use Richness\RichPayments\Models\PaymentMethod;
use Richness\RichPayments\RichPayments;
use Richness\RichPayments\Support\RichPaymentsViews;

final class CheckoutController extends Controller
{
    public function methods(): View
    {
        return view(RichPaymentsViews::CHECKOUT_METHODS, [
            'gateways' => PaymentGateway::query()
                ->with(['methods' => fn ($query) => $query->where('active', true)->orderBy('sort_order')])
                ->where('active', true)
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function start(Request $request, RichPayments $payments): RedirectResponse
    {
        $data = $request->validate([
            'gateway' => ['required', 'string', 'max:64'],
            'method' => ['nullable', 'string', 'max:64'],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'currency' => ['nullable', 'string', 'size:3'],
            'merchant_reference' => ['required', 'string', 'max:255'],
            'customer.name' => ['nullable', 'string', 'max:255'],
            'customer.email' => ['nullable', 'email', 'max:255'],
            'customer.phone' => ['nullable', 'string', 'max:32'],
        ]);

        $gateway = PaymentGateway::query()->where('code', $data['gateway'])->where('active', true)->firstOrFail();
        $method = isset($data['method'])
            ? PaymentMethod::query()->where('gateway_id', $gateway->id)->where('code', $data['method'])->where('active', true)->firstOrFail()
            : null;

        $session = $payments->start($gateway, new PaymentRequest(
            amountMinor: (int) $data['amount_minor'],
            currency: $data['currency'] ?? (string) config('rich-payments.default_currency', 'EGP'),
            merchantReference: $data['merchant_reference'],
            methodCode: $method?->code,
            customer: $data['customer'] ?? [],
            notificationUrl: route('rich-payments.webhook', ['gateway' => $gateway->code]),
            redirectionUrl: route('rich-payments.response', ['gateway' => $gateway->code]),
        ));

        if (! $session->checkoutUrl) {
            return redirect()->route('rich-payments.failed')->with('rich_payments_error', 'تعذر إنشاء رابط الدفع.');
        }

        return redirect()->away($session->checkoutUrl);
    }

    public function pending(Request $request): RedirectResponse|View
    {
        if ($request->query() !== [] && ! $request->has('reference')) {
            return redirect()->to(route('rich-payments.response', [
                'gateway' => (string) config('rich-payments.default_gateway', 'paymob'),
            ]).'?'.http_build_query($request->query()));
        }

        $reference = $request->query('reference');

        return view(RichPaymentsViews::RESULT_PENDING, [
            'reference' => $reference,
        ]);
    }

    public function status(
        Request $request,
        string $reference,
        \Richness\RichPayments\Gateways\GatewayManager $manager,
        ApplyPaymentResult $applyPaymentResult,
    ): JsonResponse
    {
        $attempt = PaymentAttempt::query()
            ->where('merchant_reference', $reference)
            ->latest()
            ->first();

        if (! $attempt) {
            return response()->json(['status' => 'not_found'], 404);
        }

        if ($attempt->status !== PaymentStatus::Paid && $attempt->status !== PaymentStatus::Failed) {
            $transactionId = $attempt->transactions()->latest()->value('external_transaction_id')
                ?? $attempt->external_reference;

            if ($transactionId) {
                try {
                    $gateway = PaymentGateway::query()->where('code', $attempt->gateway_code)->first();
                    if ($gateway) {
                        $result = $manager->driver($gateway)->inquire($gateway, (string) $transactionId);
                        $applyPaymentResult->fromInquiry($gateway, $result);
                        $attempt->refresh();
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Payment inquiry failed during status check', [
                        'reference' => $reference,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $status = $attempt->status;
        $redirectUrl = null;

        if ($status === PaymentStatus::Paid || $status === PaymentStatus::Failed) {
            $sessionKey = config('rich-payments.response_verified_reference_session_key');
            if (is_string($sessionKey) && $sessionKey !== '') {
                $references = (array) $request->session()->get($sessionKey, []);
                $references[] = $reference;
                $request->session()->put($sessionKey, array_values(array_unique($references)));
            }

            $route = config('rich-payments.response_redirect_route');
            if (is_string($route) && $route !== '') {
                $parameter = config('rich-payments.response_redirect_parameter', 'order');
                $redirectUrl = route($route, [(string) $parameter => $reference]);

                $message = $status === PaymentStatus::Paid
                    ? 'تم تأكيد الدفع بنجاح.'
                    : 'عملية الدفع غير ناجحة. يمكنك المحاولة مرة أخرى من صفحة الطلب.';
                $request->session()->flash('status', $message);
            } else {
                $redirectUrl = route($status === PaymentStatus::Paid ? 'rich-payments.success' : 'rich-payments.failed');
            }
        }

        return response()->json([
            'status' => $status->value,
            'redirect_url' => $redirectUrl,
        ]);
    }

    public function success(): View
    {
        return view(RichPaymentsViews::RESULT_SUCCESS);
    }

    public function failed(): View
    {
        return view(RichPaymentsViews::RESULT_FAILED);
    }
}
