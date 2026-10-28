# Resume point (as of 2026-10-28)

Prompt source of truth: the Laravel 13, 32-task prompt pack.

## Status

| Task | State | Notes |
|---|---|---|
| 01 UI audit | Docs done | Original browser screenshots and console log were not captured. |
| 02 Requirements | Draft done | Some original product decisions remain open; see `docs/product-spec.md`. |
| 03 Architecture and API contract | Design done | Original OpenAPI draft has planned endpoints that differ from current Task 13/14 routes. |
| 04 Repo setup | Scaffold done | Lockfiles are present. Clean-clone verification and GitHub settings still need owner verification. |
| 05 Laravel migration and existing UI integration | Verified | See task 05 documentation. |
| 06 CI, testing, and developer tooling | Verified | PR #1 merged; CI passed. |
| 07 Core Eloquent data model | Complete for approved scope (OTP deferred) | OTP/VerificationChallenge remain deferred. |
| 08 Database migrations, seeders, and constraints | Verified | See task 08 documentation. |
| 09 Staff authentication | Verified | See task 09 documentation. |
| 10 Authorization and audit controls | Verified | See task 10 documentation. |
| 11 Staff patient management | Verified | See task 11 documentation. |
| 12 Services, practitioners, resources, and working hours | Merged; CI verified | PR #13 merged. |
| 13 Availability engine and timezone-correct slot generation | Merged; CI verified | PR #14 merged. |
| 14 Transactional appointment creation | Merged; CI verified | PR #15 merged; see `docs/task14-status.md`. |
| 15 Public booking verification/tracking | Deferred | OTP is owner-confirmed; provider/policy requirements are intentionally being gathered before implementation. |
| 16 Connect existing booking UI to real API | Merged; CI verified | PR #17 merged; see `docs/task16-status.md`. |

## Current state and next step

The repository's `main` branch is currently at Task 16. Task 15 is intentionally deferred while the clinic/provider requirements are gathered. Do not implement SMS, OTP verification grants, privacy consent wording, tracking/status lookup, or anti-abuse policy that depends on unresolved owner decisions.

Task 16 connects the existing Blade/Vite booking wizard to the real versioned service and availability APIs and the transactional appointment API from Tasks 13/14. It preserves the existing UI and remains fail-closed for phone verification.

## Rules for resuming

- Do not redo verified tasks.
- Work on one task at a time and use a separate branch for each task.
- Record each finished task here and in `docs/STATUS.md`.
- Do not claim a task is verified without evidence.
- Do not merge task branches to `main` without explicit owner authorization.
