# Contributing to ZAP

Thanks for helping. Product behavior lives in [`docs/SDD.md`](docs/SDD.md). UI layout and pt-BR copy follow `preview (6).html`. On conflict, the SDD wins on behavior; the mockup wins on appearance within MVP.

## Language

- Code, comments, commits, branches, pull requests, issues, and technical docs: **English**.
- Product UI copy: **Portuguese (pt-BR)**.

## Local runtime

Docker Compose is the only supported way to run ZAP.

```bash
cp .env.example .env
# Set ADMIN_EMAIL and ADMIN_PASSWORD first.
docker compose up --build
```

Open [http://localhost:8000](http://localhost:8000).

Do not add `VITE_EVOLUTION_*` variables. Evolution credentials stay on the server.

## Development workflow

Trunk-based, short-lived branches:

```text
feat/instance-provisioning
fix/duplicate-webhook-events
docs/api-authentication
```

Use [Conventional Commits](https://www.conventionalcommits.org/) in English, imperative mood, no trailing period on the subject.

## Quality gates

Run the same checks CI runs before opening a PR:

```bash
composer lint
composer analyse
composer test
composer audit
npm run lint
npm run typecheck
npm run test
npm run build
npm audit --audit-level=high
php artisan scramble:export
```

The default Pest suite must not start Evolution. Use HTTP fakes and fixtures under `tests/Fixtures/Evolution/`.

Playwright covers the critical path with Evolution mocked:

```bash
npx playwright install --with-deps chromium
npx playwright test
```

## Pull requests

Use the PR template. Keep changes reviewable. Do not mix unrelated refactors with behavior changes.

Out of scope until a later SDD revision: production deploy, billing, outbound email, password reset, Evolution Manager, Pinia, and an in-app guides portal.

## License

Contributions are licensed under Apache License 2.0.
