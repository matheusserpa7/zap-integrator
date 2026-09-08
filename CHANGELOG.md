# Changelog

All notable changes to this project are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- First MVP of ZAP as a self-hosted Evolution API integrator (`v0.1.0` scope).
- Docker Compose local runtime with isolated Evolution (no Manager).
- Platform admin email allowlist and gated registration.
- Async WhatsApp instance provisioning, QR connection, and status sync.
- Sanctum API keys with abilities, hashed secrets, and plaintext shown once.
- Public `POST /api/v1/messages/text` (`202 Accepted`) with idempotency.
- Read-only 1:1 inbox, inbound media volume, and authenticated media download.
- Customer webhooks with canonical JSON, allowlisted mapper, HMAC signatures, SSRF controls, and retries.
- OpenAPI 3.1 generation (Scramble) and Scalar API reference at `/docs/api`.
- Repository docs, ADRs, GitHub issue/PR templates, and CI quality/security gates.
- Playwright critical-path coverage with Evolution mocked.

[Unreleased]: https://github.com/zap/zap/compare/HEAD...HEAD
