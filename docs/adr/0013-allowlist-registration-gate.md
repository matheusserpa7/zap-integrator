# ADR-0013: Allowlist registration gate

## Status

Accepted

## Context

v0.1.0 has no billing and no outbound email. Open registration on a self-hosted box would be unsafe. Password reset would require mail.

## Decision

A seeded platform admin (`ADMIN_EMAIL` / `ADMIN_PASSWORD`) allowlists emails. Registration is gated on that list. Signup creates one workspace and an owner membership. Forgotten passwords are not supported: the admin deletes the user and the email may register again. Non-local boots refuse empty or well-known admin passwords.

## Consequences

There is no 2FA, invites, or multiple workspaces per user in MVP. Quotas come from environment variables (`MAX_INSTANCES_PER_WORKSPACE`). Audit events cover allowlist and user disable/delete.
