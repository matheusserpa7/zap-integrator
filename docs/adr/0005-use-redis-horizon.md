# ADR-0005: Use Redis and Horizon

## Status

Accepted

## Context

Provider HTTP, media ingest, and customer webhook delivery are unreliable. HTTP requests must not wait on Evolution. Operators need a queue dashboard.

## Decision

Redis 8.x is the cache and queue backend. Laravel Horizon supervises queues `critical`, `webhooks`, `provider`, `media`, `maintenance`, and `default`. Pulse may be used locally; Telescope is local/development only.

## Consequences

Jobs declare their queue in the constructor. Horizon is authorized for authenticated users. Dashboard queue depth is read from Redis when `QUEUE_CONNECTION=redis`; otherwise the UI shows that depth is unavailable rather than a fake number.
