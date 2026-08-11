<?php

declare(strict_types=1);

namespace Richness\RichPayments\Contracts;

use Richness\RichPayments\Data\ConnectionResult;
use Richness\RichPayments\Models\PaymentGateway;

interface SupportsConnectionTest
{
    public function testConnection(PaymentGateway $gateway): ConnectionResult;
}
