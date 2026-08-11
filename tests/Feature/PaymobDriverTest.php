<?php

declare(strict_types=1);

namespace Richness\RichPayments\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Richness\RichPayments\Actions\ApplyPaymentResult;
use Richness\RichPayments\Database\Seeders\RichPaymentsPaymobSeeder;
use Richness\RichPayments\Enums\PaymentStatus;
use Richness\RichPayments\Gateways\GatewayManager;
use Richness\RichPayments\Gateways\Paymob\PaymobGateway;
use Richness\RichPayments\Models\PaymentAttempt;
use Richness\RichPayments\Models\PaymentGateway;
use Richness\RichPayments\Security\CredentialVault;
use Richness\RichPayments\Tests\TestCase;

final class PaymobDriverTest extends TestCase
{
    public function test_refund_marks_attempt_refunded(): void
    {
        $this->artisan('db:seed', ['--class' => RichPaymentsPaymobSeeder::class])->assertExitCode(0);

        $gateway = PaymentGateway::query()->where('code', 'paymob')->firstOrFail();
        $this->app->make(CredentialVault::class)->put($gateway, 'test', 'secret_key', 'sk_test_secret');

        $attempt = PaymentAttempt::query()->create([
            'public_id' => (string) Str::ulid(),
            'amount_minor' => 20000,
            'currency' => 'EGP',
            'gateway_code' => 'paymob',
            'method_code' => 'cards',
            'status' => PaymentStatus::Paid,
            'merchant_reference' => 'ORDER-PKG-1',
            'external_reference' => '2556706',
        ]);

        Http::fake([
            'https://accept.paymob.com/api/acceptance/void_refund/refund' => Http::response([
                'id' => 880001,
                'amount_cents' => 5000,
            ]),
        ]);

        $driver = $this->app->make(GatewayManager::class)->driver($gateway);
        $this->assertInstanceOf(PaymobGateway::class, $driver);

        $result = $driver->refund($gateway, '2556706', 5000);
        $this->assertTrue($result->success);

        $this->app->make(ApplyPaymentResult::class)->fromRefund($gateway, $attempt, $result);

        $attempt->refresh();
        $this->assertSame(PaymentStatus::Refunded, $attempt->status);
        $this->assertDatabaseHas('rich_payment_transactions', [
            'attempt_id' => $attempt->id,
            'external_transaction_id' => '880001',
            'status' => PaymentStatus::Refunded->value,
        ]);
    }

    public function test_connection_test_success(): void
    {
        $this->artisan('db:seed', ['--class' => RichPaymentsPaymobSeeder::class])->assertExitCode(0);

        $gateway = PaymentGateway::query()->where('code', 'paymob')->firstOrFail();
        $vault = $this->app->make(CredentialVault::class);
        $vault->put($gateway, 'test', 'secret_key', 'sk_test_secret');
        $vault->put($gateway, 'test', 'public_key', 'pk_test_public');
        $vault->put($gateway, 'test', 'api_key', 'api-test-key');

        Http::fake([
            'https://accept.paymob.com/api/auth/tokens' => Http::response(['token' => 'auth-token']),
        ]);

        $driver = $this->app->make(GatewayManager::class)->driver($gateway);
        $result = $driver->testConnection($gateway);

        $this->assertTrue($result->success);
    }
}
