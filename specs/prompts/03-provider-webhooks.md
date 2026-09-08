# ZAP — Prompt 03: Milestone 3 Provider Webhooks

You are implementing **only Milestone 3** of ZAP. M0–M2 already exist.

## Mandatory reading (in this order)

1. `specs/constitution.md`
2. `ZAP_SDD.md` sections **12.5–12.6, 14, 15, 22, 23, 24, 26, 27, 29.4, 48, 49 (M3)**
3. `specs/tasks.md` — complete **T034–T042**

Do not implement customer webhook endpoints, HMAC delivery, inbox models, or public API keys.

## Goal

Evolution can POST events to ZAP on the Docker network. ZAP authenticates them, de-duplicates, enqueues, normalizes into ZAP events, and updates instance QR/connection state in realtime.

## Inbound endpoint (§14)

```text
POST /internal/webhooks/evolution/{instancePublicId}
```

- Custom header e.g. `X-ZAP-Evolution-Secret`
- ≥256 bits entropy, encrypted at rest, rotatable
- Constant-time comparison
- Max payload size
- Minimal sync work → `202 Accepted`
- Business logic only in queued jobs (`provider` / `critical`)

## Idempotency (§14.3)

Preferred key:

1. provider event/message ID when stable;
2. provider message ID + event type;
3. hash of canonicalized payload + instance + event type.

Duplicates must not double-update in a harmful way and must not later double-deliver (prepare the fingerprint store so M5/M6 can reuse it).

## Normalization (§15)

Map Evolution names to ZAP names. Examples:

| Evolution | ZAP |
|-----------|-----|
| `QRCODE_UPDATED` | `instance.qr.updated` |
| `CONNECTION_UPDATE` | `instance.connection.updated` |
| `MESSAGES_UPSERT` | `message.received` or `message.updated` |
| `MESSAGES_UPDATE` | `message.updated` |
| `SEND_MESSAGE` | `message.sent` |

Do **not** persist inbox rows or media in this milestone. You may persist a slim internal event/fingerprint record needed for idempotency and status updates.

Public contract must stay ZAP-owned. Store raw provider payload only as needed for debug/retention fields, never as the customer-facing shape.

## Realtime + reconciliation

- Broadcast `InstanceQrUpdated`, `InstanceConnectionChanged` (and provision failed if applicable)
- Private channel `private-workspaces.{workspacePublicId}` with membership auth
- Do not broadcast credentials, QR secrets in logs, or media bytes
- Scheduled reconciliation every 5 minutes: compare ZAP connection state with `MessagingProvider::getConnectionState` for recently active instances. Inbox is **not** reconciled from Evolution.

## Fixtures

Commit sanitized payloads under `tests/Fixtures/Evolution/`.

Tests:

- wrong secret → reject
- duplicate event → one effective process
- QR / CONNECTION_UPDATE update instance status
- ingest returns 202 without calling Evolution
- reconciliation repairs a stale disconnected/connected mismatch (HTTP fake)

## Acceptance

- Evolution Manager still absent
- Endpoint is not a public customer API
- SDD §48
- `specs/tasks.md` T034–T042 checked

## Stop when

Instance status/QR stay in sync via webhooks + reconciliation. Do not build the inbox or customer delivery worker.

At the end, print: “next prompt = `specs/prompts/04-public-api-keys.md`”.
