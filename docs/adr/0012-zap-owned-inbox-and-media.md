# ADR-0012: ZAP-owned inbox and media

## Status

Accepted

## Context

Customers need a read-only 1:1 inbox and a way to download inbound media without talking to Evolution. Storing media in Evolution or rendering files in the inbox would expand MVP scope and leak provider details.

## Decision

Persist slim `Contact`, `Conversation`, and `Message` rows in ZAP. Non-text types use placeholders such as `[image]`. Inbound files go to a Docker volume with non-guessable paths. `GET /api/v1/media/{media}` requires `media:read`. Customer webhooks include ZAP media metadata, not Evolution URLs.

## Consequences

The inbox has no composer. Media expires via a retention job. Expired download attempts fail closed. Inbox UI does not render images or audio.
