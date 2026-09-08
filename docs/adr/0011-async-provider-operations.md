# ADR-0011: Async provider operations

## Status

Accepted

## Context

Evolution HTTP is slow and can fail. Browsers and API clients should not wait on QR generation, send, or webhook fan-out.

## Decision

Mutating provider work is queued. Instance create persists `creating` and dispatches `ProvisionInstance`. Public text send persists `sending` and returns HTTP `202 Accepted` with a `msg_` id. Provider webhooks ingest with `202` after fingerprinting. Customer deliveries run on the `webhooks` queue.

## Consequences

The JSON `data.status` on text send is `sending`, not a second "accepted" resource state. Clients poll, subscribe to Reverb, or wait for customer webhooks. Jobs are unique where double-provisioning would hurt. Horizon makes retries observable.
