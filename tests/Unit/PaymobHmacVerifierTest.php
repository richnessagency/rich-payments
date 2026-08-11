<?php

declare(strict_types=1);

namespace Richness\RichPayments\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Richness\RichPayments\Gateways\Paymob\PaymobHmacVerifier;

final class PaymobHmacVerifierTest extends TestCase
{
    public function test_it_verifies_transaction_processed_hmac_using_documented_field_order(): void
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
                'order' => ['id' => 4778239],
                'owner' => 4705,
                'pending' => false,
                'source_data' => [
                    'pan' => 2346,
                    'sub_type' => 'MasterCard',
                    'type' => 'card',
                ],
                'success' => true,
            ],
        ];

        $secret = 'merchant-hmac-secret';
        $expectedMessage = '1002020-03-25T18:39:44.719228EGPfalsefalse25567066741truefalsefalsefalsetruefalse47782394705false2346MasterCardcardtrue';
        $hmac = hash_hmac('sha512', $expectedMessage, $secret);

        $verifier = new PaymobHmacVerifier;

        $this->assertTrue($verifier->verify($payload, $secret, $hmac));
        $this->assertFalse($verifier->verify($payload, $secret, 'invalid'));
    }

    public function test_it_rejects_missing_secret_or_hmac(): void
    {
        $verifier = new PaymobHmacVerifier;

        $this->assertFalse($verifier->verify([], null, 'abc'));
        $this->assertFalse($verifier->verify([], 'secret', null));
    }

    public function test_it_verifies_card_token_hmac_using_documented_field_order(): void
    {
        $payload = [
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
                'next_payment_intention' => 'pi_test_2a9c29ead1734ce8ad09ae4936019992',
            ],
        ];

        $secret = 'merchant-hmac-secret';
        $expectedMessage = 'MasterCard2024-11-13T12:32:23.859982test@test.com8555026xxxx-xxxx-xxxx-2346246628264064419e98aceb96f5a370ddf46460db9d555f88bf12448f80e1839b39f78ab';
        $hmac = hash_hmac('sha512', $expectedMessage, $secret);

        $verifier = new PaymobHmacVerifier;

        $this->assertTrue($verifier->verify($payload, $secret, $hmac));
        $this->assertFalse($verifier->verify($payload, $secret, 'invalid'));
    }

    public function test_it_verifies_flattened_transaction_response_hmac(): void
    {
        $flattened = [
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
            'owner' => 4705,
            'pending' => 'false',
            'source_data_pan' => 2346,
            'source_data_sub_type' => 'MasterCard',
            'source_data_type' => 'card',
            'success' => 'true',
        ];

        $secret = 'merchant-hmac-secret';
        $expectedMessage = '1002020-03-25T18:39:44.719228EGPfalsefalse25567066741truefalsefalsefalsetruefalse47782394705false2346MasterCardcardtrue';
        $hmac = hash_hmac('sha512', $expectedMessage, $secret);

        $verifier = new PaymobHmacVerifier;

        $this->assertTrue($verifier->verify($flattened, $secret, $hmac));
        $this->assertFalse($verifier->verify($flattened, $secret, strrev($hmac)));
    }
}
