# Security Policy

## Supported versions

Security fixes are accepted on `main` until the first tagged release line is published. After `v0.1.0`, the latest minor release receives patches.

## Reporting a vulnerability

Do **not** open a public issue for a security defect.

Use GitHub's private vulnerability reporting on this repository (Security → Advisories → Report a vulnerability). Include:

- a description of the issue and impact;
- steps to reproduce;
- affected version or commit;
- any known workaround.

We will acknowledge the report and follow up with a fix or a reasoned decline.

## Secrets and logging

Never log Authorization headers, API keys, tokens, webhook secrets, passwords, full QR payloads, full message bodies, or media bytes.

Do not expose Evolution API credentials to the browser. There are no `VITE_EVOLUTION_*` variables.

Customer webhook URLs are validated against SSRF controls on both Test and live delivery. Webhook mapping is allowlisted; it is not a JavaScript sandbox.

## Dependency updates

Dependabot opens pull requests for Composer, npm, GitHub Actions, and Docker base images. Major-version upgrades are not auto-merged. CI runs `composer audit` and `npm audit`.
