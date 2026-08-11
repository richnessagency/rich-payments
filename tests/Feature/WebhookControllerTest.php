<?php

declare(strict_types=1);

namespace Richness\RichPayments\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Richness\RichPayments\Database\Seeders\RichPaymentsPaymobSeeder;
use Richness\RichPayments\Enums\PaymentStatus;
use Richness\RichPayments\Events\WebhookRejected;
use Richness\RichPayments\Models\PaymentAttempt;
use Richness\RichPayments\Models\PaymentGateway;
use Richness\RichPayments\Security\CredentialVault;
use Richness\RichPayments\Tests\TestCase;

final class WebhookControllerTest extends TestCase
{
    public function test_transaction_processed_webhook_updates_attempt_and_records_transaction(): void
    {
        Event::fake([WebhookRejected::class]);

        [$gateway, $attempt] = $this->transactionWebhookState('ORDER-WH1');

        $response = $this->postJson($this->webhookUrl(), $this->transactionPayload('ORDER-WH1')[0]);

        $response->assertOk()->assertJson(['ok' => true, 'verified' => true]);

        $attempt->refresh();
        $this->assertSame(PaymentStatus::Paid, $attempt->status);
        $this->assertNotNull($attempt->paid_at);

        $this->assertDatabaseHas('rich_payment_transactions', [
            'attempt_id' => $attempt->id,
            'external_transaction_id' => '2556706',
            'status' => PaymentStatus::Paid->value,
            'success' => true,
        ]);

        $this->assertDatabaseHas('rich_payment_webhook_events', [
            'gateway_code' => 'paymob',
            'verified' => true,
        ]);

        Event::assertNotDispatched(WebhookRejected::class);
    }

    public function test_card_token_webhook_is_verified_without_creating_a_transaction(): void
    {
        Event::fake([WebhookRejected::class]);

        $this->artisan('db:seed', ['--class' => RichPaymentsPaymobSeeder::class])->assertExitCode(0);
        $gateway = PaymentGateway::query()->where('code', 'paymob')->firstOrFail();
        $this->app->make(CredentialVault::class)->put($gateway, 'test', 'hmac_secret', 'merchant-hmac-secret');

        $tokenMessage = 'MasterCard2024-11-13T12:32:23.859982test@test.com8555026xxxx-xxxx-xxxx-2346246628264064419e98aceb96f5a370ddf46460db9d555f88bf12448f80e1839b39f78ab';
        $hmac = hash_hmac('sha512', $tokenMessage, 'merchant-hmac-secret');
        $url = route('rich-payments.webhook', ['gateway' => 'paymob']).'?hmac='.$hmac;

        $response = $this->postJson($url, $this->cardTokenPayload());

        $response->assertOk()->assertJson(['ok' => true, 'verified' => true]);

        $this->assertDatabaseHas('rich_payment_webhook_events', [
            'gateway_code' => 'paymob',
            'verified' => true,
        ]);
        $this->assertDatabaseCount('rich_payment_transactions', 0);
        Event::assertNotDispatched(WebhookRejected::class);
    }

    public function test_invalid_hmac_is_rejected_and_attempt_is_not_updated(): void
    {
        Event::fake([WebhookRejected::class]);

        [$gateway, $attempt] = $this->transactionWebhookState('ORDER-WH2');

        [$payload] = $this->transactionPayload('ORDER-WH2');
        $payload['obj']['id'] = 9999999;

        $url = route('rich-payments.webhook', ['gateway' => 'paymob']).'?hmac=invalid-signature';
        $this->postJson($url, $payload)
            ->assertOk()
            ->assertJson(['ok' => true, 'verified' => false]);

        $attempt->refresh();
        $this->assertSame(PaymentStatus::Initiated, $attempt->status);
        $this->assertDatabaseCount('rich_payment_transactions', 0);
        $this->assertDatabaseHas('rich_payment_webhook_events', [
            'gateway_code' => 'paymob',
            'verified' => false,
        ]);

        Event::assertDispatched(WebhookRejected::class);
    }

    public function test_transaction_response_get_callback_redirects_to_success(): void
    {
        Event::fake([WebhookRejected::class]);

        [$gateway, $attempt] = $this->transactionWebhookState('ORDER-WH3');

        $url = route('rich-payments.response', ['gateway' => 'paymob']);
        $this->get($url.'?'.http_build_query($this->flattenedResponsePayload('ORDER-WH3')))
            ->assertRedirect(route('rich-payments.success'));

        $attempt->refresh();
        $this->assertSame(PaymentStatus::Paid, $attempt->status);
        $this->assertDatabaseHas('rich_payment_transactions', [
            'attempt_id' => $attempt->id,
            'external_transaction_id' => '2556706',
            'success' => true,
        ]);
        Event::assertNotDispatched(WebhookRejected::class);
    }

    /**
     * @return array{0: PaymentGateway, 1: PaymentAttempt}
     */
    private function transactionWebhookState(string $reference): array
    {
        $this->artisan('db:seed', ['--class' => RichPaymentsPaymobSeeder::class])->assertExitCode(0);

        $gateway = PaymentGateway::query()->where('code', 'paymob')->firstOrFail();
        $this->app->make(CredentialVault::class)->put($gateway, 'test', 'hmac_secret', 'merchant-hmac-secret');

        $attempt = PaymentAttempt::query()->create([
            'public_id' => (string) Str::ulid(),
            'amount_minor' => 100,
            'currency' => 'EGP',
            'gateway_code' => 'paymob',
            'method_code' => 'cards',
            'status' => PaymentStatus::Initiated,
            'merchant_reference' => $reference,
        ]);

        return [$gateway, $attempt];
    }

    /**
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function transactionPayload(string $reference): array
    {
        $payload = [
            'obj' => [
                'amount_cents' => 100,
                'created_at' => '2020-03-25T18:39:44.719228',
                'currency' => 'EGP',
                'error_occured' => false,
                'has_parent_transaction' => false,
                'id' => 2556706,
                'integration_id' => 6741,
                'is_3d_secure' => true,
                'is_auth' => false,
                'is_capture' => false,
                'is_refunded' => false,
                'is_standalone_payment' => true,
                'is_voided' => false,
                'order' => ['id' => 4778239, 'merchant_order_id' => $reference],
                'owner' => 4705,
                'pending' => false,
                'source_data' => [
                    'pan' => 2346,
                    'sub_type' => 'MasterCard',
                    'type' => 'card',
                ],
                'success' => true,
                'special_reference' => $reference,
            ],
        ];

        $message = '1002020-03-25T18:39:44.719228EGPfalsefalse25567066741truefalsefalsefalsetruefalse47782394705false2346MasterCardcardtrue';

        return [$payload, $message];
    }

    /**
     * @return array<string, mixed>
     */
    private function cardTokenPayload(): array
    {
        return [
            'type' => 'TOKEN',
            'obj' => [
                'id' => 8555026,
                'token' => 'e98aceb96f5a370ddf46460db9d555f88bf12448f80e1839b39f78ab',
                'masked_pan' => 'xxxx-xxxx-xxxx-2346',
                'merchant_id' => 246628,
                'card_subtype' => 'MasterCard',
                'created_at' => '2024-11-13T12:32:23.859982',
                'email' => 'test@test.com',
                'order_id' => '264064419',
                'user_added' => false,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function flattenedResponsePayload(string $reference): array
    {
        $payload = [
            'amount_cents' => 100,
            'created_at' => '2020-03-25T18:39:44.719228',
            'currency' => 'EGP',
            'error_occured' => 'false',
            'has_parent_transaction' => 'false',
            'id' => 2556706,
            'integration_id' => 6741,
            'is_3d_secure' => 'true',
            'is_auth' => 'false',
            'is_capture' => 'false',
            'is_refunded' => 'false',
            'is_standalone_payment' => 'true',
            'is_voided' => 'false',
            'order_id' => 4778239,
            'merchant_order_id' => $reference,
            'owner' => 4705,
            'pending' => 'false',
            'source_data_pan' => 2346,
            'source_data_sub_type' => 'MasterCard',
            'source_data_type' => 'card',
            'success' => 'true',
        ];

        $message = '1002020-03-25T18:39:44.719228EGPfalsefalse25567066741truefalsefalsefalsetruefalse47782394705false2346MasterCardcardtrue';
        $payload['hmac'] = hash_hmac('sha512', $message, 'merchant-hmac-secret');

        return $payload;
    }

    private function webhookUrl(): string
    {
        $hmac = hash_hmac('sha512', $this->transactionPayload('ORDER-WH1')[1], 'merchant-hmac-secret');

        return route('rich-payments.webhook', ['gateway' => 'paymob']).'?hmac='.$hmac;
    }
}
