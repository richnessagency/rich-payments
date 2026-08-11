<?php

declare(strict_types=1);

namespace Richness\RichPayments;

use Illuminate\Support\ServiceProvider;
use Richness\RichPayments\Gateways\GatewayManager;
use Richness\RichPayments\Security\AuditLogger;
use Richness\RichPayments\Security\CredentialVault;

final class RichPaymentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/rich-payments.php', 'rich-payments');

        $this->app->singleton(CredentialVault::class);
        $this->app->singleton(GatewayManager::class);
        $this->app->singleton(RichPayments::class);
        $this->app->singleton(AuditLogger::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'rich-payments');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        $this->publishes([
            __DIR__.'/../config/rich-payments.php' => config_path('rich-payments.php'),
        ], 'rich-payments-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/rich-payments'),
        ], 'rich-payments-views');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'rich-payments-migrations');
    }
}
