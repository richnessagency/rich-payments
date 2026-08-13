# Changelog

All notable changes to RichPayments will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.3] - 2026-08-13

### Fixed

- Kept credential masked previews short and database-safe for long Paymob API keys.

## [0.1.2] - 2026-08-13

### Fixed

- Removed a redundant Paymob checkout URL condition so static analysis passes after the credential guard added in 0.1.1.

## [0.1.1] - 2026-08-13

### Changed

- Made RichPayments migrations safe for shared-hosting MySQL key-length limits by shortening indexed string columns.
- Made package table creation idempotent so a partially failed migration can continue without manual database access.

### Fixed

- Paymob session creation now fails early with a clear error when credentials or active integration IDs are missing.

## [0.1.0] - 2026-08-11

### Added

- Paymob gateway driver (intention API, unified checkout, webhook HMAC verification).
- Encrypted database credentials via `CredentialVault` with masked previews.
- Audit logging for credential rotation, method changes, and money actions.
- Payment attempts, transactions, and webhook event storage with deduplication.
- Refund, void, capture, and transaction inquiry money actions.
- Ready Blade views (checkout methods, result pages, admin screens).
- Orchestra Testbench test suite, PHPStan config, and GitHub Actions CI.
