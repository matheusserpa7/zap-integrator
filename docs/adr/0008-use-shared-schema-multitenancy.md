# ADR-0008: Use shared-schema multi-tenancy

## Status

Accepted

## Context

MVP creates one workspace per registered user. Isolation must hold even if a second workspace appears later. Sequential IDs must not leak across tenants.

## Decision

Use a shared PostgreSQL schema. Every tenant row has `workspace_id`. Policies and route bindings resolve models by `public_id` within the current workspace. Cross-workspace access returns 404, not 403, when existence must stay hidden.

## Consequences

There is no database-per-tenant or schema-per-tenant. Tests assert tenancy on HTTP and policy layers. Multiple workspaces per user remain a future enhancement.
