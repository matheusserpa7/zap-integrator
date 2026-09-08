# ZAP — Prompt 07: Milestone 7 Documentation and OSS Polish

You are implementing **only Milestone 7** of ZAP. M0–M6 already exist. This is the last MVP prompt before `v0.1.0`.

## Mandatory reading (in this order)

1. `specs/constitution.md`
2. `ZAP_SDD.md` sections **21, 28, 29.3, 32–36, 41–44, 48–50**
3. `preview (6).html` — screenshots source
4. `specs/tasks.md` — complete **T070–T078**

Do not add production deployment guides, billing, outbound email, or an in-app documentation portal (`/docs/getting-started`).

## Goal

The repository looks like a serious open-source product: a stranger can run it, understand the contract, and trust the quality gates.

## Repository docs (§41)

Place the SDD at `docs/SDD.md` (copy or move `ZAP_SDD.md`; leave a short root pointer if you move it).

Required files:

```text
README.md
LICENSE              # Apache-2.0 (already from M0 — complete it)
CHANGELOG.md
CONTRIBUTING.md
SECURITY.md
CODE_OF_CONDUCT.md
docs/adr/
.github/ISSUE_TEMPLATE/
.github/pull_request_template.md
```

README sections (§41): overview, screenshots, architecture diagram, features, stack, Docker-only setup, Evolution notes (bundled, internal, no Manager), testing, configuration, security, trademark disclaimer (§43), license.

Disclaimer (required):

```text
ZAP is an independent open-source project and is not affiliated with,
endorsed by, or sponsored by WhatsApp or Meta.
```

## ADRs (§42)

Write accepted ADRs 0001–0016 with Context / Decision / Consequences. They must match what the code actually does — if implementation diverged, either fix the code or record the real decision.

## API reference (§21.2, §44)

- Scramble → OpenAPI 3.1
- Scalar UI (link from app/README — **not** a guides portal)
- CI fails if generation fails
- Examples in cURL, JavaScript/TypeScript, and PHP (comments in English)

## Quality / security automation (§35–36)

Ensure PR CI matches SDD §35. Add Dependabot or Renovate, CodeQL, composer audit, npm audit. Do not auto-merge major upgrades. Still **no** deploy workflow. Still **no** Evolution container in PR CI.

## Playwright (§29.3)

Critical path with Evolution **mocked**:

1. platform admin allowlists an email
2. user registers into a workspace
3. creates instance
4. simulated event marks instance connected
5. creates API key
6. sends text via API (202)
7. inbound fixture creates inbox row + media object
8. customer webhook delivery visible
9. builder preview matches fixture; test does not block save
10. media download succeeds with the API key

Run on `main` and on PRs that touch critical flows.

## Dashboard metrics (§28.2, §3.1)

Replace remaining placeholder dashboard numbers with real counters (instances, webhook success/retry/dead-letter, queue depth as available). No fake “availability %”.

## Screenshots

Capture or generate README screenshots from the real UI (or mockup-faithful pages): dashboard, inbox, builder, instance QR, API keys.

## Acceptance

- First-release scope in §50 is documented as implemented
- `v0.1.0` changelog section exists (unreleased until the user tags)
- SDD §48
- `specs/tasks.md` T070–T078 checked
- Master progress table in `specs/tasks.md` marked complete

## Stop when

OSS polish is done. Do not start future enhancements from §51.

At the end, print a `v0.1.0` readiness checklist (what still blocks a tag).
