<?php

declare(strict_types=1);

namespace Richness\RichPayments\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Richness\RichPayments\Actions\ApplyPaymentResult;
use Richness\RichPayments\Contracts\ManagesTransactions;
use Richness\RichPayments\Enums\PaymentStatus;
use Richness\RichPayments\Gateways\GatewayManager;
use Richness\RichPayments\Models\PaymentAttempt;
use Richness\RichPayments\Models\PaymentGateway;
use Richness\RichPayments\Security\AuditLogger;
use Richness\RichPayments\Support\RichPaymentsViews;

final class AttemptController extends Controller
{
    public function index(): View
    {
        return view(RichPaymentsViews::ADMIN_ATTEMPTS_INDEX, [
            'attempts' => PaymentAttempt::query()
                ->with('transactions')
                ->latest()
                ->paginate(25),
        ]);
    }

    public function inquire(Request $request, PaymentAttempt $attempt, GatewayManager $manager, ApplyPaymentResult $applyPaymentResult, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'external_transaction_id' => ['nullable', 'string', 'max:255'],
        ]);

        $transactionId = $data['external_transaction_id']
            ?? $attempt->transactions()->latest()->value('external_transaction_id')
            ?? $attempt->external_reference;

        if (! $transactionId) {
            return back()->with('status', 'لا يوجد رقم معاملة خارجي يمكن الاستعلام عنه.');
        }

        $gateway = PaymentGateway::query()->where('code', $attempt->gateway_code)->firstOrFail();
        $result = $manager->driver($gateway)->inquire($gateway, (string) $transactionId);
        $applyPaymentResult->fromInquiry($gateway, $result);
        $audit->record($gateway, 'attempt.inquired', PaymentAttempt::class, $attempt->id, [
            'found' => $result->found,
            'status' => $result->status,
        ]);

        return back()->with('status', $result->found ? 'تم الاستعلام وتحديث حالة الدفع.' : 'لم يتم العثور على المعاملة لدى بوابة الدفع.');
    }

    public function refund(Request $request, PaymentAttempt $attempt, GatewayManager $manager, ApplyPaymentResult $applyPaymentResult, AuditLogger $audit): RedirectResponse
    {
        if ($attempt->status !== PaymentStatus::Paid) {
            return back()->with('status', 'لا يمكن رد مبلغ لمحاولة غير مدفوعة.');
        }

        $data = $request->validate([
            'external_transaction_id' => ['required', 'string', 'max:255'],
            'amount_minor' => ['required', 'integer', 'min:1'],
        ]);

        $gateway = PaymentGateway::query()->where('code', $attempt->gateway_code)->firstOrFail();
        $driver = $manager->driver($gateway);

        if (! $driver instanceof ManagesTransactions) {
            return back()->with('status', 'البوابة لا تدعم الرد التلقائي للمبالغ.');
        }

        $result = $driver->refund($gateway, $data['external_transaction_id'], (int) $data['amount_minor']);
        $applyPaymentResult->fromRefund($gateway, $attempt, $result);
        $audit->record($gateway, 'attempt.refunded', PaymentAttempt::class, $attempt->id, [
            'success' => $result->success,
            'amount_minor' => $result->amountMinor,
            'failure_reason' => $result->failureReason,
        ]);

        return back()->with(
            'status',
            $result->success ? 'تم إرسال طلب الرد إلى بوابة الدفع بنجاح.' : 'فشل الرد: '.($result->failureReason ?? 'خطأ غير معروف.'),
        );
    }

    public function void(Request $request, PaymentAttempt $attempt, GatewayManager $manager, ApplyPaymentResult $applyPaymentResult, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'external_transaction_id' => ['required', 'string', 'max:255'],
        ]);

        $gateway = PaymentGateway::query()->where('code', $attempt->gateway_code)->firstOrFail();
        $driver = $manager->driver($gateway);

        if (! $driver instanceof ManagesTransactions) {
            return back()->with('status', 'البوابة لا تدعم إلغاء المعاملات.');
        }

        $result = $driver->void($gateway, $data['external_transaction_id']);
        $applyPaymentResult->fromRefund($gateway, $attempt, $result);
        $audit->record($gateway, 'attempt.voided', PaymentAttempt::class, $attempt->id, [
            'success' => $result->success,
            'failure_reason' => $result->failureReason,
        ]);

        return back()->with(
            'status',
            $result->success ? 'تم إلغاء المعاملة بنجاح.' : 'فشل الإلغاء: '.($result->failureReason ?? 'خطأ غير معروف.'),
        );
    }

    public function capture(Request $request, PaymentAttempt $attempt, GatewayManager $manager, ApplyPaymentResult $applyPaymentResult, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'external_transaction_id' => ['required', 'string', 'max:255'],
            'amount_minor' => ['required', 'integer', 'min:1'],
        ]);

        $gateway = PaymentGateway::query()->where('code', $attempt->gateway_code)->firstOrFail();
        $driver = $manager->driver($gateway);

        if (! $driver instanceof ManagesTransactions) {
            return back()->with('status', 'البوابة لا تدعم العملية Capture.');
        }

        $result = $driver->capture($gateway, $data['external_transaction_id'], (int) $data['amount_minor']);
        $applyPaymentResult->fromRefund($gateway, $attempt, $result);
        $audit->record($gateway, 'attempt.captured', PaymentAttempt::class, $attempt->id, [
            'success' => $result->success,
            'failure_reason' => $result->failureReason,
        ]);

        return back()->with(
            'status',
            $result->success ? 'تم تأكيد تحصيل المبلغ بنجاح.' : 'فشل التحصيل: '.($result->failureReason ?? 'خطأ غير معروف.'),
        );
    }
}
