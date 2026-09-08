# ADR-0004: Use PostgreSQL for ZAP

## Status

Accepted

## Context

ZAP persists workspaces, instances, inbox rows, media metadata, webhook events, and hashed API tokens. The database must support constraints, JSON, and concurrent writes from queue workers.

## Decision

ZAP uses PostgreSQL 18.x (pinned Compose image). Tests may use SQLite in memory. Evolution has its own Postgres instance and must not share ZAP tables.

## Consequences

Migrations target PostgreSQL features used by the app. CI starts Postgres 18 for Pest. Local setup does not document a host PHP/Postgres install; Docker Compose is the runtime.
