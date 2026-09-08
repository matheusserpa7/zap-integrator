# ADR-0014: Apache License 2.0

## Status

Accepted

## Context

ZAP is intended as a serious open-source product. Contributors and operators need an explicit patent grant and trademark clarity around WhatsApp/Meta.

## Decision

License the project under Apache License 2.0. Keep the required trademark disclaimer in the README and NOTICE. Do not imply ZAP is an official WhatsApp product. Do not vendor Evolution source unless its license is reviewed.

## Consequences

`LICENSE`, `NOTICE`, and README carry the disclaimer. Evolution remains an external dependency with its own terms. Contributions are Apache-2.0.
