<?php

declare(strict_types=1);

namespace Richness\RichPayments\Tests;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Orchestra\Testbench\TestCase as Orchestra;
use Richness\RichPayments\RichPaymentsServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [RichPaymentsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        tap($app['config'], function (ConfigRepository $config): void {
            $config->set('app.key', env('APP_KEY', 'base64:oGbPdNN6HDYDQuHYJy1Xn/bWlJPtdReGomdnbsEnINk='));
            $config->set('database.default', 'testbench');
            $config->set('database.connections.testbench', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        });
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
