# ADR-0015: CI without deploy

## Status

Accepted

## Context

The SDD revision does not include production deployment. PR CI still needs to prove quality, generate OpenAPI, and audit dependencies. Starting Evolution in GitHub Actions would be slow and flaky.

## Decision

GitHub Actions on pull requests run Composer validation, Pint, Larastan, Pest (Postgres + Redis services), ESLint, TypeScript, Vitest, Vite build, OpenAPI generation, Composer/npm audit, and a Docker image build. Playwright runs on `main` and on PRs that touch critical flows, with Evolution mocked. There is no deploy workflow. Dependabot and CodeQL are enabled. Major upgrades are not auto-merged.

## Consequences

`main` must stay runnable via Docker Compose, but GitHub does not ship to a VPS. Contributors cannot rely on CI as a live WhatsApp lab.
