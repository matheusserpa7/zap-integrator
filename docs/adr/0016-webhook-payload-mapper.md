# ADR-0016: Webhook payload mapper

## Status

Accepted

## Context

Customers often need a CRM-shaped JSON body. Allowing arbitrary JavaScript or unrestricted templates would be an injection and SSRF footgun. One endpoint must not subscribe to every event type.

## Decision

Each customer endpoint has exactly one event type. Default payload is ZAP canonical JSON. Custom maps support field, fixed, and mustache-like expression modes over allowlisted paths only. The builder preview uses committed fixtures. Test delivery does not block save.

## Consequences

No JS expressions in MVP. Duplicate endpoint mints a new secret. Mapping validation lives in domain code and is covered by unit and feature tests. Preview in the UI must match the catalog fixture for the selected event.
