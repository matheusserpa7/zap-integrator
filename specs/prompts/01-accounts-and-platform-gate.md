# ZAP — Prompt 01: Milestone 1 Accounts and Platform Gate

You are implementing **only Milestone 1** of ZAP. Milestone 0 already exists.

## Mandatory reading (in this order)

1. `specs/constitution.md`
2. `ZAP_SDD.md` sections **4, 9.1–9.5, 10, 11, 25, 26, 31, 40, 48, 49 (M1)**
3. `preview (6).html` — reuse shell tokens; auth screens are **not** in the mockup
4. `specs/tasks.md` — complete **T013–T023**

Do not implement Evolution integration, instances provisioning, API keys, inbox persistence, or customer webhooks.

## Goal

A seeded platform admin allowlists an email. That email can register. Others cannot. Registration creates exactly one workspace with the user as `owner`. Session auth protects the Inertia app.

## Domain

### Users

Fields per §9.1: `public_id` (`usr_...`), `name`, `email`, `password`, `is_platform_admin`, `disabled_at`. **No** `email_verified_at`. ZAP sends no email.

### Two authorization axes (§9.2)

- **Platform:** whitelist, list/disable/delete users, later billing. Seeded admin from env.
- **Workspace:** MVP is a single `owner` membership. No team UI, invites, or extra roles.

Do not collapse platform admin into `workspaces.role = admin`.

### EmailAllowlist

Unique normalized email. Registration succeeds only when the email is present, the user is not disabled, and the account does not already exist. This gate is the future billing hook.

### Workspace + WorkspaceMember

One workspace per user, created during registration. No “create workspace” UI. Keep membership table so teams can be added later.

Public IDs: prefix + UUIDv7. Never expose sequential ids in URLs.

## Behavior

1. Bootstrap/seeder creates or updates `ADMIN_EMAIL` / `ADMIN_PASSWORD` with `is_platform_admin=true`.
2. Refuse to boot in non-local environments if `ADMIN_PASSWORD` is empty or a well-known default.
3. Session login / logout / CSRF / secure cookies.
4. Registration Form Request enforces allowlist.
5. Forgotten password: unsupported. Escape hatch: platform admin deletes the user; allowlisted email may register again.
6. Platform pages: `Pages/Platform/Allowlist/Index.vue`, `Pages/Platform/Users/Index.vue`.
7. Auth pages: `Pages/Auth/Login.vue`, `Pages/Auth/Register.vue` — match existing design system, pt-BR copy.
8. Policies for platform vs workspace. Route model binding must not bypass ownership.
9. Quota env default `MAX_INSTANCES_PER_WORKSPACE=1` (enforced later in M2, stored/read now).
10. Audit log table + events: allowlist add/remove, user disable/delete. Never store secrets in audit metadata.

## Tests (Pest)

- Unlisted email cannot register.
- Allowlisted email registers and receives workspace + owner membership.
- Disabled user cannot log in.
- Non-admin cannot open platform routes.
- Platform admin cannot read another workspace’s future tenant data by ID guessing (authorization tests).
- Admin seeder is idempotent.
- Cross-workspace access denied.

## Acceptance

- First-party app uses **session** auth, not API tokens
- No mailers, Mailpit, verification, reset, or 2FA
- UI pt-BR, code English
- SDD §48 Definition of Done
- `specs/tasks.md` T013–T023 checked

## Stop when

Accounts and the platform gate work end-to-end. Do not add instance creation or Evolution calls.

At the end, print: how to log in as admin, how to allowlist, and “next prompt = `specs/prompts/02-evolution-integration.md`”.
