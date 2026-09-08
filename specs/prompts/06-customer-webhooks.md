# ZAP — Prompt 06: Milestone 6 Customer Webhooks

You are implementing **only Milestone 6** of ZAP. M0–M5 already exist.

## Mandatory reading (in this order)

1. `specs/constitution.md`
2. `ZAP_SDD.md` sections **9.11–9.13, 17, 18.2, 23, 26.8, 27, 40, 46, 48, 49 (M6)**
3. `preview (6).html` — Webhook Builder (primary UI reference)
4. `specs/tasks.md` — complete **T060–T069**

Do not add n8n-style graphs, IF nodes, delays, JavaScript expressions, or multiple event types on one endpoint.

## Goal

Customers create one endpoint per event type. Default delivery is the canonical ZAP JSON. They can personalize a map, preview, and test. Live delivery is signed, SSRF-safe, retried, and logged.

This is a **payload mapper**, not a workflow engine.

## Data model

`WebhookEndpoint`: one `event_type`, `payload_mode` `canonical | custom`, encrypted secret (≥256 bits), encrypted extra headers, `body_mapping` JSON.

`WebhookEvent`: normalized payload including media metadata, retention default 7 days.

`WebhookDelivery`: attempts, status, excerpt, next retry, dead_letter.

Duplicate = new endpoint + **new secret**, copied map.

## Canonical contract (§17.4)

Documented envelope with `id`, `type`, `created_at`, `data`. Text omits `media`; image includes ZAP media object + authenticated download path. Never Evolution JSON.

## Builder UI (§17.1)

Match mockup 3 columns:

1. **Trigger** — one event type, destination URL
2. **Map** — locked in canonical; **Personalizar** copies canonical leaves into rows; **Restaurar padrão** clears custom map
3. **Preview** — live JSON + headers

Row model: `path`, `value_mode` (`field | fixed | expression`), `value`.

- `field`: allowlisted canonical path only (catalog per event type)
- `fixed`: literal
- `expression`: mustache-like allowlisted substitutions only — no JS, filters, loops, functions
- Unknown paths fail **local** validation, never reach delivery
- Custom mode: missing runtime values become `null` (do not fail delivery)

Save does **not** require a successful Test. Local validation does (URL, SSRF, HTTPS non-local, event type, mapping, header allowlist).

**Testar**: catalog fixture, signed, `X-ZAP-Test: true`, show status/latency/excerpt. Not retried by Horizon. Same SSRF rules as live.

## Transport (§17.5–17.8)

```http
POST
Content-Type: application/json
User-Agent: ZAP-Webhooks/1.0
X-ZAP-Event-Id: evt_...
X-ZAP-Timestamp: <unix>
X-ZAP-Signature: sha256=<hex-hmac>
```

HMAC-SHA256 over `{timestamp}.{raw_request_body}` where body is the **rendered** bytes.

User headers allowlist: `Authorization`, `Accept`, `X-*` except `X-ZAP-*`. Forbidden: Host, Content-Length, Transfer-Encoding, Content-Type, User-Agent, any `X-ZAP-*`. ZAP appends signature headers last.

Only HTTP `2xx` is success. Do not follow redirects.

## Retries (§17.7)

`immediate, 30s, 2m, 10m, 1h, 6h, 24h` + jitter. Then `dead_letter`. Manual retry while payload exists. Queue: `webhooks`.

## SSRF (§17.9) — mandatory

Reject localhost, loopback, link-local, cloud metadata, reserved ranges; RFC1918 in non-local; resolve DNS then validate IP; rebinding protection; short timeouts; limit response size. Local compose may allow `http://` to host-gateway test sinks.

## Public API

Implement webhook-endpoint CRUD, test, duplicate, deliveries list, manual retry from §18.2 with `webhooks:read` / `webhooks:write`.

## Audit

Endpoint created, secret rotated, mapping changed — never store secrets.

## Tests

- Canonical HMAC verifies
- Custom map output + signature over mapped body
- Allowlist rejects unknown paths and forbidden headers
- SSRF rejects private IPs (Test and live)
- Duplicate events from M3 do not double-deliver
- Retry/dead_letter
- Save without Test success
- Tenancy isolation

## Acceptance

- UI recognizable vs mockup builder
- No JS evaluation
- SDD §48
- `specs/tasks.md` T060–T069 checked

## Stop when

Builder + signed delivery + retries work. Docs/Scalar polish is M7 (update OpenAPI annotations here if endpoints are new).

At the end, print: “next prompt = `specs/prompts/07-documentation-and-oss-polish.md`”.
