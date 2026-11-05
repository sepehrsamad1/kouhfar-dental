# Resume point (2026-11-05)

Prompt source of truth: the Laravel 13, 32-task prompt pack. For repository state, use `docs/STATUS.md`; for Task 28, use `docs/task28-status.md` and the security documents under `docs/security/`. This file is a current handoff, not a replacement for the original task prompts.

## Current state

- Task 27 is documented as CI-verified in `docs/STATUS.md`.
- Task 28 local synthetic-data prototype implementation is complete on branch `task/28-security-privacy-audit`.
- PR #30 is open: https://github.com/sepehrsamad1/kouhfar-dental/pull/30
- Code verification: CI run #1149 passed on commit `05f307a15e8ce69480253f1bd1bde24d3969f423`: 328 Laravel/Pest tests (24,731 assertions), 36 Playwright tests, PostgreSQL migrations, Pint, PHPStan, Composer/npm audits, JavaScript lint/typecheck/build, Task 27 deterministic evaluation and full-history Gitleaks.
- Documentation follow-up: CI run #1150 passed on docs-only commit `33a1db6f39f2ae8a60b028f3bf04b0b87ba03091`.
- PR has not been merged. Do not merge without explicit owner authorization.

## Task 28 scope and production boundary

The prototype uses synthetic patients, phone numbers and images only. MeliPayamak, Kavenegar and live AI providers remain disabled. No production or third-party systems were tested.

Production remains blocked pending full database/files backup and isolated restore evidence, separate backup storage/key/ACLs, TLS/proxy/database/storage/secrets/logging evidence, complete route-by-route IDOR/CSRF/DOM-XSS/distributed-rate-limit verification, clinic/legal retention review, provider URL-path log review and an approved lost-authenticator identity-proofing procedure. See the findings register for SEC-28-003, SEC-28-006, SEC-28-008, SEC-28-014 and SEC-28-015.

Retention periods selected for the prototype are engineering defaults, not universal legal standards. Do not use real patient data or enable live providers until the remaining legal and operational controls are reviewed.

## Next task

The formal Task 29 definition was not found in the repository branch during the Task 28 review. Retrieve the exact Task 29 prompt from the original 32-task prompt pack before starting it; do not infer or invent the task scope.

## Rules for resuming

- Do not redo verified tasks; confirm the current branch and CI status first.
- Work on one task at a time and use a separate branch for each task.
- Record task state in `docs/STATUS.md` and the task-specific status file.
- Do not claim verification without CI or other explicit evidence.
- Do not merge task branches to `main` without explicit owner authorization.
- Keep production and third-party testing out of scope unless separately authorized in writing.
