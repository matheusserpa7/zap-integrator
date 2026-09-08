# ADR-0010: Isolate Evolution persistence

## Status

Accepted

## Context

Evolution can persist messages, contacts, and history. ZAP already owns the inbox and media metadata. Duplicating that data inside Evolution would create two sources of truth and a larger backup surface.

## Decision

Give Evolution its own Postgres, Redis, and session volume. Keep instance persistence on. Turn message, contact, and historic persistence off. ZAP stores only what the product needs.

## Consequences

Losing the Evolution volume may require scanning a QR again; ZAP inbox rows remain. Operators must not point Evolution at the ZAP database. Provider payloads are slimmed before storage and expire via retention jobs.
