<?php

declare(strict_types=1);

namespace Richness\RichPayments\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Richness\RichPayments\Models\PaymentAuditLog;
use Richness\RichPayments\Support\RichPaymentsViews;

final class AuditLogController extends Controller
{
    public function index(): View
    {
        return view(RichPaymentsViews::ADMIN_AUDIT_LOGS_INDEX, [
            'logs' => PaymentAuditLog::query()
                ->with('gateway')
                ->latest()
                ->paginate(50),
        ]);
    }
}
