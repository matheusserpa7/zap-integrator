# ADR-0003: Use a MessagingProvider interface

## Status

Accepted

## Context

ZAP is an integrator on top of Evolution API. Domain code must not depend on Evolution payload shapes, HTTP paths, or error codes. Tests must run without a live Evolution container.

## Decision

Domain messaging speaks `App\Domain\Messaging\Contracts\MessagingProvider` and ZAP DTOs. `EvolutionMessagingProvider` implements the interface. A fake implementation (`App\Integrations\Fake\FakeMessagingProvider`) is bound when `MESSAGING_DRIVER=fake` for Playwright and can be swapped in Pest tests.

## Consequences

Provider names stay internal. Public API, Inertia props, and customer webhooks expose ZAP types. HTTP fakes and committed fixtures under `tests/Fixtures/Evolution/` cover the default suite. A future Cloud API provider can implement the same interface without rewriting inbox or webhooks.
