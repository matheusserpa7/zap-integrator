# ADR-0006: Use Sanctum API tokens

## Status

Accepted

## Context

Customers call `/api/v1` from servers. The first-party UI already uses session cookies. Tokens must be scoped, revocable, and shown in plaintext only once.

## Decision

Use Laravel Sanctum personal access tokens with ZAP abilities (`instances:read|write`, `messages:send|read`, `media:read`, `webhooks:read|write`). Tokens are hashed at rest, prefixed `zap_live_` for display, and last-used is recorded. Public API middleware is not session/CSRF; CORS is not permissive.

## Consequences

Revoke immediately blocks the API. Missing ability returns 403. Rate limits apply per token and workspace. Audit events record create and revoke. JSON resources never include sequential primary keys; public IDs use prefix + UUIDv7.
