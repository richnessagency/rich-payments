# RichPayments

RichPayments is a plug and play Laravel payment package for multiple payment gateways.
It ships with encrypted database credentials, ready Blade views, admin screens, webhook storage,
and a Paymob driver as the first supported gateway.

## Install

```bash
composer require richnessagency/rich-payments
php artisan migrate
php artisan db:seed --class="Richness\\RichPayments\\Database\\Seeders\\RichPaymentsPaymobSeeder"
```

## Publish Assets

```bash
php artisan vendor:publish --tag=rich-payments-config
php artisan vendor:publish --tag=rich-payments-views
php artisan vendor:publish --tag=rich-payments-migrations
```

## Admin

Open:

```text
/admin/rich-payments/gateways
```

Paymob starts disabled. Add the encrypted keys and Integration IDs from the admin screen, then enable
the gateway and the methods you want to show to customers.

Admin screens:

- Gateway list with enable/disable status.
- Gateway settings with encrypted keys (masked after save) and a **connection test** button.
- Payment methods with Integration IDs, per-method service fees, and enable/disable.
- Payment attempts with **Transaction Inquiry**, **Refund**, **Void**, and **Capture** actions.
- Audit log of every credential rotation, method change, and money action (no secret values stored).

## Paymob Fields

Required credentials:

- `secret_key`
- `public_key`
- `hmac_secret`
- `api_key` for inquiry, connection tests, and reconciliation flows

Payment method Integration IDs are stored per method:

- `cards`
- `wallets`
- `kiosk`
- `bnpl`

## Money Actions

Paymob supports refund, void, and capture through the driver. Each action records a `payment_transactions`
row, updates the attempt status (`refunded` / `cancelled`), and dispatches a `PaymentRefunded` event.
Partial refunds and captures are supported via the amount field.

## Security

- Secret values are encrypted before being saved to the database.
- Saved credentials are never shown again in full.
- Webhook payloads are redacted before storage.
- Redirect pages are not treated as proof of payment.
- Verified webhooks are the source of truth.
- Audit logs store key names and masks only, never raw secret values.

## Package Status

This is an early `0.1.0` implementation. The package is ready for local integration testing, but
before publishing a production release you should complete live Paymob sandbox testing and finalize
exact HMAC field ordering against the latest Paymob documentation for every callback type you enable.

The package ships its own test suite (Orchestra Testbench, in-memory SQLite) plus PHPStan and Pint
configs for standalone CI.
