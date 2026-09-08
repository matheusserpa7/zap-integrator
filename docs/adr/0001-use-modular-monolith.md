# ADR-0001: Use a modular monolith

## Status

Accepted

## Context

ZAP must ship as a self-hosted integrator that a stranger can run with Docker Compose. Splitting accounts, messaging, and webhooks into services would add operational cost without a proven scale problem.

## Decision

Keep a Laravel modular monolith. Domain folders live under `app/Domain/{Accounts,ApiKeys,Conversations,Instances,Media,Messaging,Platform,Webhooks}`. Eloquent models stay in `app/Models`. Evolution lives only in `app/Integrations/Evolution`. Controllers stay thin and call Actions.

## Consequences

New product behavior is added as domain actions, not as a new deployable. Microservices, Kafka, and generic repositories stay out of scope. Boundaries are enforced by folders, policies, and the `MessagingProvider` seam rather than network hops.
