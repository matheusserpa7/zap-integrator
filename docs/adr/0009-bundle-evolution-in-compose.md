# ADR-0009: Bundle Evolution in Compose

## Status

Accepted

## Context

ZAP is useless without a WhatsApp provider. Asking operators to install Evolution separately would break the plug-and-play story. Evolution Manager is an extra surface and is not required.

## Decision

Docker Compose is the only supported local runtime and includes Evolution with a pinned image. ZAP talks to Evolution on the Docker network (`http://evolution:8080`). Evolution is published only on loopback as `127.0.0.1:8080` in development. Evolution Manager is not included.

## Consequences

`.env.example` holds placeholders only. No production VPS/Kubernetes playbook ships in this SDD revision. PR CI does not start the Evolution container.
