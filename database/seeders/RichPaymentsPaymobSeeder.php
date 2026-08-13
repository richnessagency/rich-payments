<?php

declare(strict_types=1);

namespace Richness\RichPayments\Database\Seeders;

use Illuminate\Database\Seeder;
use Richness\RichPayments\Models\PaymentGateway;

final class RichPaymentsPaymobSeeder extends Seeder
{
    public function run(): void
    {
        $gateway = PaymentGateway::query()->updateOrCreate(
            ['code' => 'paymob'],
            [
                'name' => 'Paymob',
                'environment' => 'test',
                'active' => false,
                'sort_order' => 10,
                'capabilities' => [
                    'intention_api' => true,
                    'unified_checkout' => true,
                    'webhook_hmac' => true,
                    'transaction_inquiry' => true,
                ],
            ],
        );

        foreach ([
            'cards' => ['ar' => 'بطاقات بنكية', 'en' => 'Cards', 'sort' => 10, 'fees' => ['percent' => '2.75', 'fixed_minor' => 300]],
            'wallets' => ['ar' => 'محافظ إلكترونية', 'en' => 'Mobile Wallets', 'sort' => 20, 'fees' => ['percent' => '2.75', 'fixed_minor' => 300]],
            'kiosk' => ['ar' => 'دفع كاش عبر كشك', 'en' => 'Kiosk', 'sort' => 30, 'fees' => ['percent' => '2.75', 'fixed_minor' => 300]],
            'bnpl' => ['ar' => 'تقسيط / ادفع لاحقاً', 'en' => 'BNPL', 'sort' => 40, 'fees' => ['percent' => '2.75', 'fixed_minor' => 300]],
        ] as $code => $method) {
            $gateway->methods()->updateOrCreate(
                ['code' => $code],
                [
                    'display_name_ar' => $method['ar'],
                    'display_name_en' => $method['en'],
                    'active' => false,
                    'sort_order' => $method['sort'],
                    'fees_config' => $method['fees'],
                ],
            );
        }

        $this->command?->info('RichPayments Paymob gateway and payment methods are ready. Add encrypted credentials from the admin screen.');
    }
}
