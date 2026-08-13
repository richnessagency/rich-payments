<?php

declare(strict_types=1);

namespace Richness\RichPayments\Tests\Feature;

use Richness\RichPayments\Database\Seeders\RichPaymentsPaymobSeeder;
use Richness\RichPayments\Models\PaymentCredential;
use Richness\RichPayments\Models\PaymentGateway;
use Richness\RichPayments\Security\CredentialVault;
use Richness\RichPayments\Tests\TestCase;

final class CredentialVaultTest extends TestCase
{
    public function test_it_seeds_paymob_gateway_and_methods(): void
    {
        $this->artisan('db:seed', ['--class' => RichPaymentsPaymobSeeder::class])->assertExitCode(0);

        $gateway = PaymentGateway::query()->where('code', 'paymob')->first();

        $this->assertNotNull($gateway);
        $this->assertEqualsCanonicalizing(
            ['cards', 'wallets', 'kiosk', 'bnpl'],
            $gateway->methods()->pluck('code')->all(),
        );
    }

    public function test_it_encrypts_and_masks_credentials(): void
    {
        $this->artisan('db:seed', ['--class' => RichPaymentsPaymobSeeder::class])->assertExitCode(0);

        $gateway = PaymentGateway::query()->where('code', 'paymob')->firstOrFail();
        $vault = $this->app->make(CredentialVault::class);

        $vault->put($gateway, 'test', 'secret_key', 'sk_live_secret_value');

        $credential = PaymentCredential::query()->where('key_name', 'secret_key')->firstOrFail();
        $this->assertNotSame('sk_live_secret_value', $credential->encrypted_value);
        $this->assertSame('sk_live_secret_value', $vault->get($gateway, 'secret_key'));

        $this->assertDatabaseHas('rich_payment_audit_logs', ['action' => 'credential.rotated']);
    }

    public function test_it_keeps_masked_preview_short_for_long_credentials(): void
    {
        $this->artisan('db:seed', ['--class' => RichPaymentsPaymobSeeder::class])->assertExitCode(0);

        $gateway = PaymentGateway::query()->where('code', 'paymob')->firstOrFail();
        $vault = $this->app->make(CredentialVault::class);
        $longValue = str_repeat('pk_live_long_secret_', 40).'TAIL';

        $vault->put($gateway, 'test', 'api_key', $longValue);

        $credential = PaymentCredential::query()->where('key_name', 'api_key')->firstOrFail();

        $this->assertLessThanOrEqual(191, mb_strlen((string) $credential->masked_preview));
        $this->assertSame('••••••••••••TAIL', $credential->masked_preview);
        $this->assertSame($longValue, $vault->get($gateway, 'api_key'));
    }

    public function test_it_returns_null_for_missing_credential(): void
    {
        $this->artisan('db:seed', ['--class' => RichPaymentsPaymobSeeder::class])->assertExitCode(0);

        $gateway = PaymentGateway::query()->where('code', 'paymob')->firstOrFail();

        $this->assertNull($this->app->make(CredentialVault::class)->get($gateway, 'secret_key'));
    }
}
