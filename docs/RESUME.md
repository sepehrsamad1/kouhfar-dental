# Resume point (as of 2026-10-27)

Prompt source of truth: the Laravel 13, 32-task prompt pack.

## Status

| Task | State | Notes |
|---|---|---|
| 01 UI audit | Docs done | Original browser screenshots and console log were not captured. |
| 02 Requirements | Draft done | Some original product decisions remain open; see the updated statuses in `docs/product-spec.md` section 2. |
| 03 Architecture and API contract | Design done | Contract was not validated against a running server. |
| 04 Repo setup | Scaffold done | Lockfiles are present. Clean-clone verification and GitHub settings still need owner verification. |
| 05 Laravel migration and existing UI integration | Verified | See `docs/task05-visual-diff.md` and `docs/task05-status.md`. |
| 06 CI, testing, and developer tooling | Verified | PR #1 merged into `main`; GitHub Actions passed 1/1 checks. Local PostgreSQL integration test was skipped; CI uses PostgreSQL. |
| 07 Core Eloquent data model | Complete for approved scope (OTP deferred) | Model classes, factories, and configuration tests are merged into main. OTP and VerificationChallenge are deferred. Database-backed checks remain for Task 08. |
| 08 Database migrations, seeders, and constraints | Verified | See `docs/task08-status.md`. |
| 09 Staff authentication | Verified | See `docs/task09-status.md`. |
| 10 Authorization and audit controls | Verified | See `docs/task10-status.md`. |
| 11 Staff patient management | Verified | See `docs/task11-status.md`; CI run #90 passed on `main`. |
| 12 Services, practitioners, resources, and working hours | In progress | See `docs/task12-status.md`; use branch `task/12-clinic-configuration`. |

## Next step

Tasks 07 through 11 are implemented in the current `main` history; Task 11 is supported by CI run #90. OTP and `VerificationChallenge` remain deferred pending an owner decision. Task 12 baseline code passed CI run `40425347718` on commit `42ad11761afeaae56965dca3749b6acbcb100419` (129 Laravel tests, 604 assertions, 4 browser smoke tests). The owner has since confirmed that two dental units are shared between practitioners and one consultation space exists. An inactive starter catalog, typed resource requirements, admin-managed practitioner-service assignment controls, and regression tests have been added on `task/12-clinic-configuration`. CI run `40430514875` passed on code commit `340ec1729d620a48d248a09e402ac999155f791b` (133 Laravel tests, 629 assertions, 4 browser smoke tests). Actual practitioner identities/qualifications and assignments remain intentionally unconfigured until clinic management supplies and reviews them; responsive visual checks have not been captured.

## Rules for resuming

- Do not redo verified tasks.
- Work on one task at a time.
- Record each finished task here and in `docs/STATUS.md`.
- Do not claim a task is verified without evidence.
