# Project status

Last updated: 2026-10-27.

| Package | Status | Notes |
|---|---|---|
| 1 Shell audit | Done (docs only) | Browser screenshots were not taken for the original audit. |
| 2 Requirements | Draft, partially superseded by later owner decisions | See `docs/product-spec.md`; do not assume all D1-D16 remain unresolved. |
| 3 Architecture and contract | Done (design) | Contract was not validated against a running server. |
| 4 Repo setup | Scaffold done | GitHub repository is active. Repository settings still need owner verification. |
| 5 Modularize UI | VERIFIED | See `docs/task05-visual-diff.md` and `docs/task05-status.md`. |
| 6 CI, testing, and developer tooling | VERIFIED | PR #1 merged into main; GitHub Actions passed 1/1 checks. Local PostgreSQL integration test was skipped; CI uses PostgreSQL. |
| 7 Core Eloquent data model | Complete for approved scope (OTP deferred) | Models, factories, and configuration tests are merged into main. OTP and VerificationChallenge are deferred. |
| 8 Database migrations, seeders, and constraints | VERIFIED | PostgreSQL schema, booking overlap protection, synthetic guarded seeding, factories, and Task 8 tests are implemented. CI run #40 passed on commit `8b971a181d45bf9de05c9ecb9a26ee3221311060`. |
| 9 Staff authentication | VERIFIED | Laravel staff session authentication, Argon2id password hashing, throttling, disabled-account handling, logout/session invalidation, and timeout enforcement are implemented. CI run #58 (40394941109) passed. |
| 10 Authorization and audit controls | VERIFIED | Task 10 role matrix, Policies/Gates, practitioner scope, IDOR tests, and minimal audit metadata are implemented. CI run #73 (40401175212) passed. |
| 11 Staff patient management | VERIFIED | `docs/task11-status.md` records CI run #78 passing. Merge commit `ee82cb56bd7ed6b8f4422db3159f3a3a656b0e8d` is on main; CI run #90 (40409553811) passed on main. |
| 12 Services, practitioners, resources, and working hours | IN PROGRESS | Branch `task/12-clinic-configuration`; configuration models, staff screen, routes, validation, optimistic service version checks, appointment-impact guards, and initial feature tests are added. Remaining CRUD and schedule-conflict coverage plus CI verification are pending. |

## Decisions still requiring owner confirmation

- Resource allocation rules: dedicated/shared chairs and service-resource requirements.
- Initial service catalog and approved durations/buffers.
- Practitioner-to-service assignments and qualifications.
- Specific recurring breaks and holiday dates.
- Any product-spec decisions not explicitly superseded by later owner instructions.

OTP and VerificationChallenge remain deferred. Do not implement behavior that depends on unresolved decisions.
