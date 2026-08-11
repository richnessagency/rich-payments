<?php

declare(strict_types=1);

namespace Richness\RichPayments\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Richness\RichPayments\Contracts\SupportsConnectionTest;
use Richness\RichPayments\Gateways\GatewayManager;
use Richness\RichPayments\Models\PaymentGateway;
use Richness\RichPayments\Models\PaymentMethod;
use Richness\RichPayments\Security\AuditLogger;
use Richness\RichPayments\Security\CredentialVault;
use Richness\RichPayments\Support\RichPaymentsViews;

final class GatewayController extends Controller
{
    public function index(): View
    {
        return view(RichPaymentsViews::ADMIN_GATEWAYS_INDEX, [
            'gateways' => PaymentGateway::query()->with('methods')->orderBy('sort_order')->get(),
        ]);
    }

    public function edit(string $gateway): View
    {
        return view(RichPaymentsViews::ADMIN_GATEWAYS_EDIT, [
            'gateway' => PaymentGateway::query()->with(['methods', 'credentials'])->where('code', $gateway)->firstOrFail(),
        ]);
    }

    public function update(Request $request, string $gateway, CredentialVault $vault, AuditLogger $audit): RedirectResponse
    {
        $paymentGateway = PaymentGateway::query()->where('code', $gateway)->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'environment' => ['required', 'in:test,live'],
            'active' => ['nullable', 'boolean'],
            'credentials' => ['nullable', 'array'],
            'credentials.*' => ['nullable', 'string', 'max:4096'],
            'methods' => ['nullable', 'array'],
            'methods.*.display_name_ar' => ['required', 'string', 'max:255'],
            'methods.*.display_name_en' => ['nullable', 'string', 'max:255'],
            'methods.*.integration_identifier' => ['nullable', 'string', 'max:255'],
            'methods.*.active' => ['nullable', 'boolean'],
            'methods.*.fees_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'methods.*.fees_fixed_minor' => ['nullable', 'integer', 'min:0'],
        ]);

        $gatewayChanges = [];

        if ((bool) ($data['active'] ?? false) !== $paymentGateway->active) {
            $gatewayChanges['active'] = (bool) ($data['active'] ?? false);
        }

        if ($data['environment'] !== $paymentGateway->environment) {
            $gatewayChanges['environment'] = $data['environment'];
        }

        $paymentGateway->update([
            'name' => $data['name'],
            'environment' => $data['environment'],
            'active' => (bool) ($data['active'] ?? false),
        ]);

        if ($gatewayChanges !== []) {
            $audit->record($paymentGateway, 'gateway.updated', PaymentGateway::class, $paymentGateway->id, $gatewayChanges);
        }

        foreach ($data['credentials'] ?? [] as $key => $value) {
            $vault->put($paymentGateway, $paymentGateway->environment, (string) $key, $value);
        }

        foreach ($data['methods'] ?? [] as $methodCode => $methodData) {
            $method = $paymentGateway->methods()->where('code', $methodCode)->first();

            if (! $method) {
                continue;
            }

            $this->updateMethod($method, $methodData, $audit);
        }

        return redirect()->route('rich-payments.admin.gateways.edit', $paymentGateway->code)
            ->with('status', 'تم حفظ إعدادات بوابة الدفع بنجاح.');
    }

    public function testConnection(string $gateway, GatewayManager $manager, AuditLogger $audit): RedirectResponse
    {
        $paymentGateway = PaymentGateway::query()->where('code', $gateway)->firstOrFail();
        $driver = $manager->driver($paymentGateway);

        if (! $driver instanceof SupportsConnectionTest) {
            $audit->record($paymentGateway, 'gateway.connection_test', PaymentGateway::class, $paymentGateway->id, [
                'result' => 'unsupported',
            ]);

            return back()->with('status', 'بوابة الدفع لا تدعم اختبار الاتصال.');
        }

        $result = $driver->testConnection($paymentGateway);

        $audit->record($paymentGateway, 'gateway.connection_test', PaymentGateway::class, $paymentGateway->id, [
            'result' => $result->success ? 'ok' : 'failed',
        ]);

        return back()->with('status', $result->success ? $result->message : 'فشل اختبار الاتصال: '.($result->message ?? 'خطأ غير معروف.'));
    }

    /**
     * @param  array<string, mixed>  $methodData
     */
    private function updateMethod(PaymentMethod $method, array $methodData, AuditLogger $audit): void
    {
        $payload = [
            'display_name_ar' => $methodData['display_name_ar'],
            'display_name_en' => $methodData['display_name_en'] ?? null,
            'active' => (bool) ($methodData['active'] ?? false),
        ];

        $changes = [
            'active' => (bool) ($methodData['active'] ?? false) !== $method->active,
            'integration_rotated' => false,
            'fees_changed' => false,
        ];

        if (! empty($methodData['integration_identifier']) && $methodData['integration_identifier'] !== $method->integration_identifier) {
            $payload['integration_identifier'] = $methodData['integration_identifier'];
            $changes['integration_rotated'] = true;
        }

        $fees = $this->feesConfig($methodData);

        if ($fees !== [] && $fees !== ($method->fees_config ?? [])) {
            $payload['fees_config'] = $fees;
            $changes['fees_changed'] = true;
        }

        $method->update($payload);

        $audit->record($method->gateway()->firstOrFail(), 'method.updated', PaymentMethod::class, $method->id, $changes);
    }

    /**
     * @param  array<string, mixed>  $methodData
     * @return array{percent?: string|float, fixed_minor?: int}
     */
    private function feesConfig(array $methodData): array
    {
        $fees = [];

        if (isset($methodData['fees_percent']) && $methodData['fees_percent'] !== '') {
            $fees['percent'] = $methodData['fees_percent'];
        }

        if (isset($methodData['fees_fixed_minor']) && $methodData['fees_fixed_minor'] !== '') {
            $fees['fixed_minor'] = (int) $methodData['fees_fixed_minor'];
        }

        return $fees;
    }
}
