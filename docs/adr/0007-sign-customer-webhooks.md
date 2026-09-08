# ADR-0007: Sign customer webhooks

## Status

Accepted

## Context

Customers receive ZAP events on URLs they control. They need to verify authenticity and reject replays. Secrets must not appear in the browser after the create flash.

## Decision

Sign the rendered body with HMAC-SHA256 over `{timestamp}.{raw_body}`. Send `X-ZAP-Signature`, `X-ZAP-Timestamp`, and `X-ZAP-Event-Id`. Extra headers are allowlisted. Secrets are encrypted at rest. Test and live delivery share the same signer and SSRF checks.

## Consequences

Customers verify the signature over the bytes they received, including custom maps. HTTPS is required outside local/testing. Redirects are disabled. Failed deliveries retry with jitter and can dead-letter; operators retry from the UI while the payload exists.
