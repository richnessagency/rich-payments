# Changelog

All notable changes to RichPayments will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-08-11

### Added

- Paymob gateway driver (intention API, unified checkout, webhook HMAC verification).
- Encrypted database credentials via `CredentialVault` with masked previews.
- Audit logging for credential rotation, method changes, and money actions.
- Payment attempts, transactions, and webhook event storage with deduplication.
- Refund, void, capture, and transaction inquiry money actions.
- Ready Blade views (checkout methods, result pages, admin screens).
- Orchestra Testbench test suite, PHPStan config, and GitHub Actions CI.
