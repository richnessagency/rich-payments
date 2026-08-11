<?php

declare(strict_types=1);

namespace Richness\RichPayments\Support;

final class RichPaymentsViews
{
    public const CHECKOUT_METHODS = 'rich-payments::checkout.methods';

    public const RESULT_PENDING = 'rich-payments::results.pending';

    public const RESULT_SUCCESS = 'rich-payments::results.success';

    public const RESULT_FAILED = 'rich-payments::results.failed';

    public const ADMIN_GATEWAYS_INDEX = 'rich-payments::admin.gateways.index';

    public const ADMIN_GATEWAYS_EDIT = 'rich-payments::admin.gateways.edit';

    public const ADMIN_ATTEMPTS_INDEX = 'rich-payments::admin.attempts.index';

    public const ADMIN_AUDIT_LOGS_INDEX = 'rich-payments::admin.audit-logs.index';
}
