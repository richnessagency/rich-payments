<?php

declare(strict_types=1);

namespace Richness\RichPayments\Gateways;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Richness\RichPayments\Contracts\PaymentGatewayDriver;
use Richness\RichPayments\Models\PaymentGateway;

final class GatewayManager
{
    public function __construct(private readonly Container $container) {}

    public function driver(PaymentGateway|string $gateway): PaymentGatewayDriver
    {
        $code = $gateway instanceof PaymentGateway ? $gateway->code : $gateway;
        $driverClass = config("rich-payments.gateways.{$code}.driver");

        if (! is_string($driverClass) || ! class_exists($driverClass)) {
            throw new InvalidArgumentException("RichPayments gateway driver [{$code}] is not configured.");
        }

        $driver = $this->container->make($driverClass);

        if (! $driver instanceof PaymentGatewayDriver) {
            throw new InvalidArgumentException("RichPayments gateway driver [{$code}] must implement PaymentGatewayDriver.");
        }

        return $driver;
    }
}
