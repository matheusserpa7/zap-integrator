# ZAP — Prompt 00: Milestone 0 Foundation

You are implementing **only Milestone 0** of ZAP.

## Mandatory reading (in this order)

1. `specs/constitution.md`
2. `ZAP_SDD.md` sections **1–8, 20, 25, 30–31, 35, 37–39, 43, 48, 49 (M0 only)**
3. `preview (6).html` — visual source of truth (read the full file before any UI code)
4. `specs/tasks.md` — complete **T001–T012** and tick them when done

Do not implement M1–M7. No real auth, whitelist, Evolution client, webhooks, or public API.

## Goal

A stranger can clone the repo, run `docker compose up`, and see the Inertia shell styled like the mockup. Quality tooling and CI exist. Business features are placeholders.

## Phase A — Application skeleton

- Laravel 13.x, PHP 8.5, Vue 3, TypeScript (strict), Inertia 3, Vite, Tailwind 4.
- Folder layout from SDD §8, including empty domain directories and `app/Integrations/Evolution`.
- Eloquent models remain under `app/Models` (none required yet except defaults).
- English identifiers. UI strings that appear on placeholder pages: pt-BR from the mockup.

## Phase B — Quality toolchain

- Pint, Larastan/PHPStan (high strictness), Pest.
- ESLint, Prettier, `npm run typecheck`, Vitest, `npm run build`.
- Composer scripts: `lint`, `analyse`, `test`.
- No ignored static-analysis errors without justification.

## Phase C — Design system and shell

Extract tokens from the mockup (`--green-*`, `--ink`, `--muted`, `--line`, `--surface`, radii, shadows) into Tailwind 4 `@theme` / CSS variables.

Install shadcn-vue primitives needed by the shell: Button, Input, Card, Badge, Select, Dialog, Dropdown.

Build `resources/js/Layouts/AppLayout.vue`:

- sticky sidebar ~260px;
- brand “ZAP / Evolution Connector”;
- nav groups Workspace + Conta as in the mockup;
- topbar with workspace title + avatar placeholder.

Placeholder pages (routes + Inertia, **no** domain logic):

- `Pages/Dashboard/Index.vue`
- `Pages/Inbox/Index.vue` — split list + thread, **no** message composer (read-only)
- `Pages/Instances/Show.vue` — QR / status placeholders
- `Pages/Webhooks/Builder.vue` — **3-column** builder layout (trigger / map / preview) even if static
- `Pages/ApiKeys/Index.vue`

Replace mockup emoji with Lucide (or shadcn-vue icons). Do not imply official WhatsApp affiliation.

**Do not implement** from the mockup: billing/subscription, in-app docs portal, CRM filters, fake availability metrics.

## Phase D — Docker

Compose services from SDD §37:

`nginx`, `app`, `vite`, `postgres` (ZAP), `redis` (ZAP), `horizon`, `scheduler`, `reverb`, `evolution`, `evolution-postgres`, `evolution-redis`.

Rules:

- pin every image version (no `latest`);
- Evolution persistence isolated from ZAP;
- Evolution sessions volume `/evolution/instances`;
- Evolution message/contact/historic saves **off** if the image allows; instance persistence **on**;
- Evolution published only as `127.0.0.1:8080:8080`;
- ZAP calls `http://evolution:8080`; Evolution webhook base `http://nginx`;
- no Mailpit, no Evolution Manager, no MinIO;
- app image multi-stage; PHP runtime has no Node; non-root user; healthchecks;
- `GET /up` must **not** fail solely because Evolution is down.

`.env.example`: placeholders only. Categories from SDD §38. `ADMIN_*` empty placeholders. Never `VITE_EVOLUTION_*`.

## Phase E — OSS + CI

- Apache-2.0 `LICENSE`
- README skeleton (overview, Docker as the only setup, trademark disclaimer from §43)
- `.editorconfig`, `.gitattributes`, `.dockerignore`
- GitHub Actions: Pint, Larastan, Pest (Postgres + Redis **services**, not Evolution), ESLint, typecheck, Vitest, Vite build, Docker image build
- No production deploy workflow
- Do **not** start Evolution in PR CI

## Acceptance

- Sidebar, color, and typography are recognizable vs `preview (6).html`
- Webhook Builder grid is 3 columns even while static
- Inbox is read-only (no send input)
- `docker compose up` brings ZAP + isolated Evolution
- Quality commands and CI config exist and are green as far as this milestone can prove
- `specs/tasks.md` T001–T012 checked
- List explicitly what is deferred to M1

## Stop when

Foundation is runnable and visually aligned. Do not add Fortify/Breeze auth, models for allowlist/instances, or Evolution HTTP calls.

At the end, print: remaining risks, commands to run locally, and “next prompt = `specs/prompts/01-accounts-and-platform-gate.md`”.
