# ZAP Constitution

> Source of truth for product behavior and architecture: `docs/SDD.md` (root pointer: `ZAP_SDD.md`).  
> This file is the non-negotiable rules every implementation prompt must obey.  
> Status: Draft aligned with SDD v1.2.

## 1. Product identity

ZAP is a plug-and-play, self-hosted **integrator** on top of Evolution API. It is not a CRM, chatbot builder, workflow engine, or official WhatsApp product.

After `docker compose up`, a platform admin allowlists emails, a user registers, a WhatsApp instance is created, a QR Code is scanned, an API key is generated, and the simplified ZAP API plus signed webhooks can be consumed.

## 2. Non-negotiable principles

1. **Simple outside, robust inside.** Public contracts stay small. Complexity stays behind domain actions and the provider seam.
2. **Secure by default.** Secrets never reach the browser, logs, or public API responses.
3. **Provider details must not leak** into the public API, Inertia props, or customer webhooks unless they are intentionally part of the ZAP contract.
4. **Async work must be observable and retryable.** HTTP requests must not wait on Evolution.
5. **No Evolution API credentials in the browser.** No `VITE_EVOLUTION_*` variables.
6. **No hidden magic** where explicit domain behavior is clearer. Prefer Actions over model observers for critical workflows.
7. **Modular monolith first.** Do not introduce microservices.
8. **Docker Compose is the only supported local runtime**, including Evolution.
9. **Use stable production-ready releases**, never prerelease on `main`.
10. **Persist only what the product needs.** Evolution is a pipe, not a message store.
11. **Do not ship production deployment** in this SDD revision (no VPS, Kubernetes, TLS playbooks, billing, outbound email).

## 3. Stack (locked)

| Layer | Choice |
|---|---|
| PHP | 8.5 |
| Backend | Laravel 13.x |
| Frontend | Vue 3 + TypeScript + `<script setup>` |
| Bridge | Inertia.js 3.x |
| CSS | Tailwind CSS 4.x |
| UI primitives | shadcn-vue |
| Database (ZAP) | PostgreSQL 18.x |
| Cache/queues (ZAP) | Redis 8.x + Horizon |
| Realtime | Laravel Reverb + polling fallback |
| Tests | Pest, Vitest, Playwright |
| Quality | Pint, Larastan, ESLint, Prettier, TS strict |
| API docs | Scramble + Scalar |
| License | Apache-2.0 |

Pin image versions. Never use `:latest`.

## 4. Language

- **Code, comments, commits, branches, PRs, issues, technical docs:** English.
- **Product UI copy:** Portuguese (pt-BR), prepared for future i18n.
- Comments explain **why**, never restate **what**.

## 5. Architecture seams

- Domain folders under `app/Domain/{Accounts,ApiKeys,Conversations,Instances,Media,Messaging,Platform,Webhooks}`.
- Evolution lives only in `app/Integrations/Evolution`. Domain code speaks ZAP types via `MessagingProvider`.
- Eloquent models stay in `app/Models`. Do not introduce generic repositories.
- Controllers stay thin. Form Requests, Policies, Actions.
- No Pinia by default. Use Inertia props, composables, Reverb/Echo.
- Public IDs: prefix + UUIDv7. Never expose sequential primary keys.
- Shared-schema multi-tenancy. Every tenant row has `workspace_id`. Policies enforce it.

## 6. Security invariants

- Session auth for the first-party Inertia app. Sanctum personal access tokens for `/api/v1`.
- CSRF, `HttpOnly` cookies, `SameSite=Lax` or stricter, `nosniff`, frame protection.
- Encrypt reversible secrets at rest (provider tokens, webhook secrets, custom headers).
- Hash API tokens. Show plaintext once.
- Customer webhook URLs: SSRF controls on both Test and live delivery.
- Webhook mapping: allowlisted paths only. No JavaScript. No arbitrary expressions beyond mustache-like allowlisted substitutions.
- Never log Authorization headers, API keys, tokens, webhook secrets, passwords, full QR payloads, full message bodies, or media bytes.

## 7. Testing and Definition of Done

A feature is done only when SDD §48 is satisfied:

- behavior implemented and authorized;
- validation exists;
- tests cover primary and failure paths;
- Pint, Larastan, ESLint, TypeScript pass;
- API docs generated when relevant;
- logs have useful context without secrets;
- security implications considered;
- user-facing errors are understandable;
- repository docs updated when relevant;
- CI is green.

Default Pest suite must **not** require a live Evolution container. Use HTTP fakes and committed fixtures under `tests/Fixtures/Evolution/`.

## 8. Scope discipline

Implement **only** the prompt currently being executed.

If a later milestone is required for the current one to compile, add the smallest stub and document it. Do not silently pull forward M2–M7 work into M0/M1.

Out of scope until a future SDD revision: production deploy, billing, outbound email, password reset, teams/invites, multiple workspaces per user, Evolution Manager, media rendering in the inbox, non-text send API, groups, in-app docs portal, 2FA, Kafka, microservices.

## 9. Visual source vs functional source

| Concern | Source of truth |
|---|---|
| Behavior, architecture, security, data model | `ZAP_SDD.md` |
| Layout, density, colors, copy (pt-BR), screen structure | `preview (6).html` |
| Conflict | SDD wins on behavior; mockup wins on appearance within MVP |

Mockup items that are **not** MVP: billing/subscription, in-app documentation portal, CRM-like filters, fake “availability” metrics.

## 10. Git

Trunk-based, short-lived branches, Conventional Commits in English. `main` must always be runnable via Docker Compose. Do not commit unless the user asks.
