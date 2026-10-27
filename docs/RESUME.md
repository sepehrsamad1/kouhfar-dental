# Resume point (as of 2026-10-27)

Prompt source of truth: the Laravel 13, 32-task prompt pack.

## Status

| Task | State | Notes |
|---|---|---|
| 01 UI audit | Docs done | Original browser screenshots and console log were not captured. |
| 02 Requirements | Draft done | Some original product decisions remain open; see `docs/product-spec.md` section 2. |
| 03 Architecture and API contract | Design done | Contract was not validated against a running server. |
| 04 Repo setup | Scaffold done | Lockfiles are present. Clean-clone verification and GitHub settings still need owner verification. |
| 05 Laravel migration and existing UI integration | Verified | See `docs/task05-visual-diff.md` and `docs/task05-status.md`. |
| 06 CI, testing, and developer tooling | Verified | PR #1 merged; CI passed. |
| 07 Core Eloquent data model | Complete for approved scope (OTP deferred) | Models, factories, and configuration tests are merged. OTP and VerificationChallenge are deferred. |
| 08 Database migrations, seeders, and constraints | Verified | See `docs/task08-status.md`. |
| 09 Staff authentication | Verified | See `docs/task09-status.md`. |
| 10 Authorization and audit controls | Verified | See `docs/task10-status.md`. |
| 11 Staff patient management | Verified | See `docs/task11-status.md`; CI run #90 passed on `main`. |
| 12 Services, practitioners, resources, and working hours | Merged; CI verified | PR #13 merged to `main` as `75f3b993ccba150ac4941407dcba4f1a84438d35`. CI run `40431587997` passed on the code commit: 138 Laravel tests (649 assertions), 5 Playwright tests. See `docs/task12-status.md`. |
| 13 Availability engine and timezone-correct slot generation | Ready to start; owner decisions needed | Dependencies 03 and 12 are present. Confirm holiday/exception behavior, pending-hold expiry, minimum notice, booking horizon, and performance budget before locking behavior. |

## Current state and next step

Task 12 is merged. It provides configuration foundations and type-based shared-resource requirements; it does not yet allocate a specific free resource during slot generation. Task 13 should implement a database-independent availability domain service with a database adapter, timezone-correct UTC slots plus local display values, expired-pending capacity release, no-store availability responses, property/table tests, and representative performance measurements.

Confirmed: clinic timezone `Asia/Tehran`; hours 12:00-20:00 Saturday through Thursday; two shared dental units and one consultation space; cross-midnight working periods are prohibited. Exact holiday dates and operating policy remain unset. Do not invent practitioner assignments, qualifications, break times, or holiday closures.

OTP and VerificationChallenge remain deferred pending an owner decision.

## Rules for resuming

- Do not redo verified tasks.
- Work on one task at a time and use a separate branch for each task.
- Record each finished task here and in `docs/STATUS.md`.
- Do not claim a task is verified without evidence.
- Do not merge task branches to `main` without explicit owner authorization.
