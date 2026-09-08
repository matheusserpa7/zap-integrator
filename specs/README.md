# ZAP — Spec-Driven Development pack

This folder turns `ZAP_SDD.md` into executable prompts. Do **not** implement the whole product in one chat.

## How to execute

1. Open a **new Agent chat**.
2. Paste **one** file from `specs/prompts/` as the entire first message.
3. Let the agent finish that milestone, including tests and Definition of Done (`ZAP_SDD.md` §48).
4. Review the diff. Only then start the next prompt.
5. After each prompt, tick the matching boxes in `specs/tasks.md`.

Run them **in order**. Later prompts assume earlier milestones exist.

| Order | Prompt | SDD milestone |
|------:|--------|---------------|
| 1 | [`prompts/00-foundation.md`](prompts/00-foundation.md) | M0 Foundation |
| 2 | [`prompts/01-accounts-and-platform-gate.md`](prompts/01-accounts-and-platform-gate.md) | M1 Accounts and Platform Gate |
| 3 | [`prompts/02-evolution-integration.md`](prompts/02-evolution-integration.md) | M2 Evolution Integration |
| 4 | [`prompts/03-provider-webhooks.md`](prompts/03-provider-webhooks.md) | M3 Provider Webhooks |
| 5 | [`prompts/04-public-api-keys.md`](prompts/04-public-api-keys.md) | M4 Public API Keys |
| 6 | [`prompts/05-messaging-api-and-inbox.md`](prompts/05-messaging-api-and-inbox.md) | M5 Messaging API and Inbox |
| 7 | [`prompts/06-customer-webhooks.md`](prompts/06-customer-webhooks.md) | M6 Customer Webhooks |
| 8 | [`prompts/07-documentation-and-oss-polish.md`](prompts/07-documentation-and-oss-polish.md) | M7 Documentation and OSS Polish |

## Sources of truth

| File | Role |
|------|------|
| [`constitution.md`](constitution.md) | Non-negotiable rules |
| [`../ZAP_SDD.md`](../ZAP_SDD.md) | Full spec + architecture |
| [`../preview (6).html`](../preview%20(6).html) | Visual / UX reference |
| [`tasks.md`](tasks.md) | Master checklist (mark as you go) |

## Rules for every prompt

- Read `constitution.md` first.
- Read only the SDD sections listed in that prompt (plus any it cross-references).
- UI work: also read `preview (6).html`.
- Do not start the next milestone.
- Do not commit unless the user asks.
- UI copy in pt-BR. Code, comments, and docs in English.

## If a prompt is too large

Tell the agent: “Implement only Phase N of this prompt, then stop.” Each prompt lists internal phases for that reason.
