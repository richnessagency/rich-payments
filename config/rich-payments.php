<?php

declare(strict_types=1);
use Richness\RichPayments\Gateways\Paymob\PaymobGateway;

return [
    'route_prefix' => 'payments',
    'admin_route_prefix' => 'admin/rich-payments',

    'middleware' => [
        'checkout' => ['web'],
        'admin' => ['web', 'admin.placeholder', 'permission:payments.view'],
        'webhook' => ['api'],
    ],

    'default_currency' => 'EGP',
    'default_gateway' => 'paymob',

    'views' => [
        'site_name' => env('RICHPAYMENTS_VIEWS_SITE_NAME'),
        'logo_url' => env('RICHPAYMENTS_VIEWS_LOGO_URL'),
        'primary_color' => env('RICHPAYMENTS_VIEWS_PRIMARY_COLOR', '#111827'),
        'accent_color' => env('RICHPAYMENTS_VIEWS_ACCENT_COLOR', '#f97316'),
        'show_powered_by' => true,
    ],

    'gateways' => [
        'paymob' => [
            'driver' => PaymobGateway::class,
            'name' => 'Paymob',
            'base_url' => env('RICHPAYMENTS_PAYMOB_BASE_URL', 'https://accept.paymob.com'),
            'intention_endpoint' => '/v1/intention/',
            'unified_checkout_path' => '/unifiedcheckout/',
        ],
    ],
];
