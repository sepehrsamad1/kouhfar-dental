# Resume point (as of 2026-10-26)

Prompt source of truth: the Laravel 13, 32-task prompt pack.

## Status

| Task | State | Notes |
|---|---|---|
| 01 UI audit | Docs done | Original browser screenshots and console log were not captured. |
| 02 Requirements | Draft done | Owner decisions D1 to D16 remain open in `docs/product-spec.md` section 8. |
| 03 Architecture and API contract | Design done | Contract was not validated against a running server. |
| 04 Repo setup | Scaffold done | Lockfiles are present. Clean-clone verification and GitHub settings still need owner verification. |
| 05 Laravel migration and existing UI integration | Verified | See `docs/task05-visual-diff.md` and `docs/task05-status.md`. |
| 06 CI, testing, and developer tooling | Verified | PR #1 merged into `main`; GitHub Actions passed 1/1 checks. Local PostgreSQL integration test was skipped; CI uses PostgreSQL. |
| 07 Core Eloquent data model | Complete for approved scope (OTP deferred) | Model classes, factories, and configuration tests are merged into main. OTP and VerificationChallenge are deferred. Database-backed checks remain for Task 08. |
| 08–32 | Not started | Follow the roadmap in order. |

## Next step

Task 07 is complete for the currently approved scope, and its model work is merged into `main`. OTP and `VerificationChallenge` are deliberately deferred until an owner decision. The generic `Job` model and factory are implemented; image processing is a job type, not a separate `ImageJob` model. Task 08 covers migrations, indexes, database constraints, PostgreSQL verification, and persisted relationship tests.

## Rules for resuming

- Do not redo verified tasks.
- Work on one task at a time.
- Record each finished task here and in `docs/STATUS.md`.
- Do not claim a task is verified without evidence.
