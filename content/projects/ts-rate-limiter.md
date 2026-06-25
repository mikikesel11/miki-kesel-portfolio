---
title: ts-rate-limiter
year: 2026
tags: [TypeScript, Hono, Rate Limiting]
featured: true
links:
  repo: https://github.com/mikikesel11/ts-rate-limiter
---
An in-memory rate-limiting middleware for HTTP APIs, built on Hono and TypeScript.
It enforces per-org (tiered) and per-endpoint budgets with a sliding-window-counter
algorithm, hot-reloadable YAML config, and standards-friendly 429s (Retry-After and
X-RateLimit-* headers) — behind a pluggable store interface so the in-memory backend
can swap for Redis without touching the middleware.
