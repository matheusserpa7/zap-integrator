# ZAP — Master task list

Aligned with `ZAP_SDD.md` §49. Check items as prompts complete.

Legend: `[P]` can be done in parallel with adjacent `[P]` tasks in the same phase. IDs are stable; do not renumber.

---

## M0 — Foundation

**Prompt:** `specs/prompts/00-foundation.md`  
**Independent test:** `docker compose up` serves the Inertia shell; CI quality workflow exists; no auth/business logic required.

- [x] T001 Create Laravel 13 + Vue 3 + TypeScript + Inertia 3 application skeleton
- [x] T002 [P] Configure Pint, Larastan (strict), Pest, ESLint, Prettier, TypeScript strict, Vitest
- [x] T003 [P] Add domain folder skeleton per SDD §8 (`app/Domain/*`, `app/Integrations/Evolution`)
- [x] T004 Extract design tokens from `preview (6).html` into Tailwind 4 theme variables
- [x] T005 Install shadcn-vue primitives (Button, Input, Card, Badge, Select, Dialog, Dropdown)
- [x] T006 Build `AppLayout` (sidebar 260px, brand, workspace nav, topbar) matching the mockup
- [x] T007 [P] Placeholder Inertia pages: Dashboard, Inbox, Instances/Show, Webhooks/Builder, ApiKeys
- [x] T008 Compose stack: nginx, app, vite, postgres, redis, horizon, scheduler, reverb
- [x] T009 Isolated Evolution: pinned image, evolution-postgres, evolution-redis, sessions volume
- [x] T010 `.env.example` placeholders only; no `VITE_EVOLUTION_*`; `GET /up` health
- [x] T011 [P] Apache-2.0 LICENSE, README skeleton, `.editorconfig`, `.gitattributes`, `.dockerignore`
- [x] T012 GitHub Actions quality gates (no deploy, no Evolution in PR CI)

---

## M1 — Accounts and Platform Gate

**Prompt:** `specs/prompts/01-accounts-and-platform-gate.md`  
**Independent test:** seeded admin allowlists an email; that email can register; others cannot; owner workspace is created.

- [x] T013 Users table with `public_id`, `is_platform_admin`, `disabled_at`; no `email_verified_at`
- [x] T014 Workspaces + workspace_members (single `owner` on signup)
- [x] T015 Email allowlist model, unique normalized email, platform policy
- [x] T016 Seed/update platform admin from `ADMIN_EMAIL` / `ADMIN_PASSWORD`
- [x] T017 Session auth: login, register (gated), logout; no mail, no password reset, no 2FA
- [x] T018 Auth pages (`Login`, `Register`) using existing design system (mockup has no auth screens)
- [x] T019 Platform UI: allowlist CRUD + users list/disable/delete
- [x] T020 Automatic workspace + owner membership on successful registration
- [x] T021 Audit log foundation + events for allowlist and user disable/delete
- [x] T022 Quota env `MAX_INSTANCES_PER_WORKSPACE=1`
- [x] T023 Feature tests: gate, tenancy policies, admin seeder, cross-workspace denial

---

## M2 — Evolution Integration

**Prompt:** `specs/prompts/02-evolution-integration.md`  
**Independent test:** create instance returns immediately; job talks to HTTP fake; instance page shows QR placeholder/status.

- [x] T024 `MessagingProvider` interface + ZAP DTOs (no Evolution types on the interface)
- [x] T025 `EvolutionClient` with timeouts, retries on safe ops, sanitized logs, correlation id
- [x] T026 `EvolutionMessagingProvider` implementing the interface
- [x] T027 Instance model + statuses enum + encrypted provider token + internal provider name
- [x] T028 Async create: persist `creating`, dispatch `ProvisionInstance`, redirect to show page
- [x] T029 QR fetch + Reverb event `InstanceQrUpdated` + polling fallback
- [x] T030 Connection status mapping from provider → ZAP enum
- [x] T031 Disconnect and delete (async where needed)
- [x] T032 Instances UI: index, create, show (QR + status) matching mockup
- [x] T033 HTTP fake / fixture tests; default suite does not boot Evolution

---

## M3 — Provider Webhooks

**Prompt:** `specs/prompts/03-provider-webhooks.md`  
**Independent test:** forged secret is rejected; duplicate payload does not double-process; QR/connection events update the instance.

- [x] T034 Internal route `POST /internal/webhooks/evolution/{instancePublicId}`
- [x] T035 Custom secret header, constant-time compare, encrypted at rest
- [x] T036 Fast ingest: validate, fingerprint, enqueue, `202 Accepted`
- [x] T037 Idempotency keys (provider event id → fallback hash)
- [x] T038 Normalization layer Evolution events → ZAP events (SDD §15)
- [x] T039 Queue processing on `provider` / `critical` as specified
- [x] T040 Realtime instance status + QR from normalized events
- [x] T041 Reconciliation command every 5 minutes (connection state only)
- [x] T042 Fixtures under `tests/Fixtures/Evolution/` + ingestion tests

---

## M4 — Public API Keys

**Prompt:** `specs/prompts/04-public-api-keys.md`  
**Independent test:** create key shows plaintext once; revoke blocks API; missing ability returns 403.

- [x] T043 Sanctum personal access tokens with ZAP abilities (SDD §9.7)
- [x] T044 Token prefix display (`zap_live_...`) without weakening verification
- [x] T045 Create/revoke UI; plaintext once; last used; optional expiration
- [x] T046 Public API middleware stack, no permissive CORS
- [x] T047 Rate limiting per token/workspace
- [x] T048 Audit events on create/revoke
- [x] T049 Feature tests: abilities, revoke, last-used, rate limit

---

## M5 — Messaging API and Inbox

**Prompt:** `specs/prompts/05-messaging-api-and-inbox.md`  
**Independent test:** `POST /api/v1/messages/text` returns 202; inbox shows the thread; inbound fixture creates placeholder + media object.

- [x] T050 Idempotency records for mutating public API requests
- [x] T051 Async text send (`202 Accepted`) via `MessagingProvider::sendText`
- [x] T052 Contact / Conversation / Message models; 1:1 only
- [x] T053 Read-only inbox UI (list + thread), placeholders for non-text, no composer
- [x] T054 Inbound media ingest to Docker volume; non-guessable paths
- [x] T055 Authenticated `GET /api/v1/media/{media}` (`media:read`)
- [x] T056 Fan-out hook for customer webhooks (canonical payload with media metadata; delivery may stub until M6)
- [x] T057 Retention job (inbox / media / webhook payloads)
- [x] T058 Reverb inbox events + polling fallback
- [x] T059 Tests: idempotency, quota, media download auth, expired media

---

## M6 — Customer Webhooks

**Prompt:** `specs/prompts/06-customer-webhooks.md`  
**Independent test:** canonical delivery is signed; custom map previews; Test does not block save; SSRF rejects private IPs.

- [x] T060 WebhookEndpoint: one event type per endpoint; canonical vs custom
- [x] T061 WebhookEvent persistence (7-day default) + WebhookDelivery
- [x] T062 Canonical envelope (SDD §17.4) including media object
- [x] T063 Mapper: field / fixed / expression (allowlisted paths only)
- [x] T064 HMAC-SHA256 over `{timestamp}.{raw_body}`; extra header allowlist
- [x] T065 SSRF on Test and live; HTTPS in non-local; no redirects
- [x] T066 Retry schedule with jitter; dead_letter; manual retry
- [x] T067 Builder UI (trigger → map → preview → test → save) matching mockup
- [x] T068 Duplicate endpoint (new secret); delivery history
- [x] T069 Tests: signature, mapping, SSRF, retries, idempotent fan-out

---

## M7 — Documentation and OSS Polish

**Prompt:** `specs/prompts/07-documentation-and-oss-polish.md`  
**Independent test:** README gets a stranger to `docker compose up`; Scalar renders; ADRs exist; CI generates OpenAPI.

- [x] T070 Move/copy SDD to `docs/SDD.md`; keep root pointer if needed
- [x] T071 README complete (screenshots, architecture, Docker, disclaimer)
- [x] T072 CONTRIBUTING, SECURITY, CODE_OF_CONDUCT, CHANGELOG, PR/issue templates
- [x] T073 ADRs 0001–0016 as listed in SDD §42
- [x] T074 Scramble OpenAPI 3.1 + Scalar; CI fails if generation fails
- [x] T075 Examples: cURL, JavaScript/TypeScript, PHP
- [x] T076 Dependabot/Renovate + CodeQL + composer/npm audit in CI
- [x] T077 Playwright critical path with Evolution mocked (SDD §29.3)
- [x] T078 Dashboard metrics from real counters (no fake availability)

---

## Progress

| Milestone | Status |
|-----------|--------|
| M0 Foundation | complete |
| M1 Accounts and Platform Gate | complete |
| M2 Evolution Integration | complete |
| M3 Provider Webhooks | complete |
| M4 Public API Keys | complete |
| M5 Messaging API and Inbox | complete |
| M6 Customer Webhooks | complete |
| M7 Documentation and OSS Polish | complete |
