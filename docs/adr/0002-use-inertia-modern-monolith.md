# ADR-0002: Use Inertia as a modern monolith

## Status

Accepted

## Context

The first-party UI is a dense dashboard (inbox, instance QR, webhook builder, API keys). A separate SPA talking to an internal JSON API would duplicate authorization and leak provider details into a second contract.

## Decision

Use Laravel + Inertia.js 3 + Vue 3 (`<script setup>`, TypeScript). Session cookies authenticate the UI. Shared props carry workspace and user. There is no Pinia store and no first-party REST API for the dashboard. Realtime uses Reverb with polling fallback.

## Consequences

Vue pages in `resources/js/Pages` render from `Inertia::render()`. Public `/api/v1` remains a customer contract only. UI copy stays pt-BR. Client code must not receive Evolution credentials or `VITE_EVOLUTION_*` variables.
