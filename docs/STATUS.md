# Project status

Last updated: 2026-10-26.

| Package | Status | Notes |
|---|---|---|
| 1 Shell audit | Done (docs only) | Browser screenshots were not taken for the original audit. |
| 2 Requirements | Done (draft) | Owner decisions D1 to D16 are still open. |
| 3 Architecture and contract | Done (design) | Contract was not validated against a running server. |
| 4 Repo setup | Scaffold done | GitHub repository is active. Repository settings still need owner verification. |
| 5 Modularize UI | VERIFIED | See docs/task05-visual-diff.md and docs/task05-status.md. |
| 6 CI, testing, and developer tooling | VERIFIED | PR #1 merged into main; GitHub Actions passed 1/1 checks. Local PostgreSQL integration test was skipped; CI uses PostgreSQL. |
| 7 Core Eloquent data model | Complete for approved scope (OTP deferred) | Model classes, factories, and configuration tests are merged into main. OTP and VerificationChallenge are deferred. Migrations and database-backed checks belong to Task 08. |
| 8 Database migrations, seeders, and constraints | VERIFIED | PostgreSQL schema, DB-level booking overlap protection, synthetic guarded seeding, factories, and Task 8 tests are implemented. GitHub Actions CI run #40 passed on commit `8b971a181d45bf9de05c9ecb9a26ee3221311060`, including PostgreSQL migrations/tests and browser smoke tests. |
| 9 Staff authentication | VERIFIED | Laravel staff session authentication, Argon2id password hashing, login throttling, disabled-account handling, logout/session invalidation, and configurable timeout enforcement are implemented. GitHub Actions CI run #45 (run ID 40393730736) passed all checks, including 95 Laravel tests and browser smoke tests. |

## Blocking owner decisions

Owner decisions D1 to D16 remain open. OTP and VerificationChallenge are deliberately deferred. Do not implement behavior that depends on unresolved decisions until they are approved.
