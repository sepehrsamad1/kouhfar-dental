# Resume point (as of 2026-10-28)

Prompt source of truth: the Laravel 13, 32-task prompt pack.

## Status

| Task | State | Notes |
|---|---|---|
| 01 UI audit | Docs done | Original browser screenshots and console log were not captured. |
| 02 Requirements | Draft done | Some original product decisions remain open; see `docs/product-spec.md`. |
| 03 Architecture and API contract | Design done | Original OpenAPI draft has planned endpoints that differ from the current Task 13/14 routes. |
| 04 Repo setup | Scaffold done | Lockfiles are present. Clean-clone verification and GitHub settings still need owner verification. |
| 05 Laravel migration and existing UI integration | Verified | See `docs/task05-visual-diff.md` and `docs/task05-status.md`. |
| 06 CI, testing, and developer tooling | Verified | PR #1 merged; CI passed. |
| 07 Core Eloquent data model | Complete for approved scope (OTP deferred) | OTP and VerificationChallenge remain deferred. |
| 08 Database migrations, seeders, and constraints | Verified | See `docs/task08-status.md`. |
| 09 Staff authentication | Verified | See `docs/task09-status.md`. |
| 10 Authorization and audit controls | Verified | See `docs/task10-status.md`. |
| 11 Staff patient management | Verified | See `docs/task11-status.md`; CI run #90 passed on `main`. |
| 12 Services, practitioners, resources, and working hours | Merged; CI verified | PR #13 merged; see `docs/task12-status.md`. |
| 13 Availability engine and timezone-correct slot generation | Merged; CI verified | PR #14 merged; see `docs/task13-status.md`. |
| 14 Transactional appointment creation | Merged; CI verified | PR #15 merged; endpoint `POST /api/v1/appointments`; see `docs/task14-status.md`. |
| 15 Public booking API | Not started | Independent backend track; D8 OTP requirement is now confirmed, but provider/subscription, budget, privacy consent and lookup details remain unresolved. |
| 16 Connect existing booking UI to real API | VERIFIED ON TASK BRANCH; MERGE PENDING OWNER APPROVAL | PR #17. CI run `40737793534` passed: 169 Laravel tests (23,613 assertions), 10 browser tests, and responsive regression screenshots at 375/768/1440.\n\n## Current state and next step

Task 16 can progress in parallel with Task 15 because its frontend track already has Task 13 availability and Task 14 atomic appointment creation. The local patch connects to those implemented endpoints; the small public catalog and versioned availability adapters were added because the frontend needs an approved public service list and date-filtered slots. OTP is owner-confirmed as required. No SMS vendor, subscription, consent policy or patient lookup is invented; a default-on fail-closed gate prevents unverified production bookings. The confirmation says the request is pending clinic approval, not that the appointment is confirmed.

Task 16 is CI-verified on its task branch. The implementation uses Task 13/14, keeps OTP fail-closed, and leaves provider/budget/privacy-consent decisions for Task 15. Merge to `main` is still a separate owner action.

## Rules for resuming

- Do not redo verified tasks.
- Work on one task at a time and use a separate branch for each task.
- Record each finished task here and in `docs/STATUS.md`.
- Do not claim a task is verified without evidence.
- Do not merge task branches to `main` without explicit owner authorization.
