# ZAP — Prompt 05: Milestone 5 Messaging API and Inbox

You are implementing **only Milestone 5** of ZAP. M0–M4 already exist.

## Mandatory reading (in this order)

1. `specs/constitution.md`
2. `ZAP_SDD.md` sections **9.8–9.10, 9.14, 16, 18.2–18.3, 22, 23, 26.4–26.7, 27, 29, 40, 48, 49 (M5)**
3. `preview (6).html` — Conversas / inbox split view
4. `specs/tasks.md` — complete **T050–T059**

Do not implement the webhook builder, custom mapping, or customer delivery retries (M6). You **may** persist a canonical `WebhookEvent` and call a fan-out action that no-ops if no endpoints exist, so M6 can hook in.

## Goal

Developers send text through `POST /api/v1/messages/text` and receive `202 Accepted`. Operators inspect 1:1 traffic in a **read-only** inbox. Inbound media is stored on a Docker volume and downloaded only with `media:read`.

## Public API

```text
POST /api/v1/messages/text
GET  /api/v1/conversations
GET  /api/v1/conversations/{conversation}
GET  /api/v1/conversations/{conversation}/messages
GET  /api/v1/media/{media}
```

Also complete instance connection/QR API endpoints from §18.2 if they are still missing (`GET connection`, `POST qr/refresh`) using M2 actions.

Send text:

- ability `messages:send`
- instance must be `connected` or domain exception `InstanceNotConnected`
- persist outbound message `accepted`/`sending`
- dispatch job on `provider` queue
- `202 Accepted` with public ids
- **Idempotency-Key** required for this mutation; store `ApiRequest` / idempotency records (§9.14); replay same response on same key+hash; conflict on same key different hash
- never wait on Evolution in the HTTP request

## Inbox (§16.1)

ZAP database is the source of truth (outbound accepts + inbound webhooks from M3).

- Direct chats only. No groups.
- UI read-only: **no composer**
- Text shows `body`
- Other types: placeholders `[image]`, `[audio]`, `[sticker]`, `[document]`, …
- Outbound status `sending` → `sent` / `failed`
- Reverb events + polling fallback
- Public ids in Inertia URLs

Extend M3 processors to create Contact / Conversation / Message rows for inbound text and non-text placeholders.

## Media (§16.2–16.4)

On inbound media:

1. Normalize metadata
2. `MessagingProvider::downloadMedia`
3. Store on Docker volume (not a public web root)
4. Disk filenames from `public_id` (not guessable)
5. Slim message row + rich event payload **without** Evolution URLs or credentials
6. `GET /api/v1/media/{media}` with `media:read` + workspace scope
7. After `expires_at` → `media_expired`; inbox row can remain until message retention

Retention job (env defaults): inbox 90d, media 7d, webhook payloads 7d, idempotency 24h.

Queue: `media` for downloads; `maintenance` for retention.

## UI

`Pages/Inbox/Index.vue` and `Show.vue` as needed. Match mockup chat shell. pt-BR. Do **not** render images/audio/stickers/documents.

## Tests

- 202 send + job faked
- idempotency replay and mismatch
- inbox authorization / tenancy
- media download 403 without ability or wrong workspace
- expired media error
- inbound fixture creates placeholder + media object (fake downloader)
- no Evolution URLs in API/Inertia payloads

## Acceptance

- Evolution remains a pipe; ZAP owns inbox/media
- SDD §48
- `specs/tasks.md` T050–T059 checked

## Stop when

Send + inbox + media download work. Customer webhook builder/delivery is M6.

At the end, print: “next prompt = `specs/prompts/06-customer-webhooks.md`”.
