# Resume point (as of 2026-10-25)

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
| 07 Core Eloquent data model | Not started | Next task. Check the owner decisions before implementing fields. |
| 08–32 | Not started | Follow the roadmap in order. |

## Next step

Start Task 07 only. Include the Master Engineering Prompt and exactly the Task 07 prompt from the prompt pack.

Before implementing, review `docs/product-spec.md` section 8 and `docs/data-model.md`. Do not invent fields that depend on unresolved owner decisions. If a required model field depends on an unresolved decision, report it as blocked and ask the owner.

## Rules for resuming

- Do not redo verified tasks.
- Work on one task at a time.
- Record each finished task here and in `docs/STATUS.md`.
- Do not claim a task is verified without evidence.
