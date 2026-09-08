# ZAP — Prompt 04: Milestone 4 Public API Keys

You are implementing **only Milestone 4** of ZAP. M0–M3 already exist.

## Mandatory reading (in this order)

1. `specs/constitution.md`
2. `ZAP_SDD.md` sections **9.7, 11.3–11.4, 18 (middleware/envelope only), 19, 26.2, 40, 48, 49 (M4)**
3. `preview (6).html` — API Keys screen
4. `specs/tasks.md` — complete **T043–T049**

Do not implement the messaging/inbox endpoints beyond a protected stub if needed for middleware smoke tests. Full `/api/v1/messages/text` is M5.

## Goal

Users create and revoke scoped API keys. The public API authenticates with `Authorization: Bearer`. Abilities are enforced. Tokens are hashed at rest and shown in plaintext only once.

## Token design (§9.7, §19)

Sanctum personal access tokens.

Abilities:

```text
instances:read
instances:write
messages:send
messages:read
media:read
webhooks:read
webhooks:write
```

- Optional expiration
- Last-used timestamp
- Revocable
- Display prefix `zap_live_...` must **not** weaken Sanctum verification
- Never persist plaintext
- Audit create/revoke (no secret in metadata)

The Inertia app must **not** use these tokens for first-party pages (session auth remains).

## Public API baseline (§18)

- Base path `/api/v1`
- JSON only
- **No** permissive browser CORS (server-to-server)
- Stable error envelope + correlation / request id
- Rate limiting per token/workspace (`RATE_LIMIT_*` env)
- Do not leak Evolution errors

You may expose read-only instance listing/show if M2 already has the models, gated by `instances:read`. Mutating instance create via API is allowed if it reuses M2 actions and `instances:write`. Do not add messaging, media, or webhook-endpoint CRUD yet.

## UI

`Pages/ApiKeys/Index.vue` matching mockup: name, abilities, created, last used, revoke, one-time plaintext flash/modal.

Copy: pt-BR.

## Tests

- Create returns plaintext once; subsequent show does not
- Revoked token cannot call API
- Missing ability → 403
- Session cookie cannot be used as a substitute for Bearer on `/api/v1` (or document if Sanctum guard is split correctly)
- Rate limit returns the documented error shape
- Last-used updates on successful request

## Acceptance

- Platform permissions, workspace policies, and token abilities stay **separate**
- SDD §48
- `specs/tasks.md` T043–T049 checked

## Stop when

Keys and API auth work. Do not build send-message or inbox APIs.

At the end, print: “next prompt = `specs/prompts/05-messaging-api-and-inbox.md`”.
