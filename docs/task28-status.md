# Task 28 — Security and Privacy Audit

**Status: BLOCKED — security/privacy audit work is in progress; production release remains blocked.**

## Task identity

- Task: 28 — Security and Privacy Audit
- Branch: `task/28-security-privacy-audit`
- Base commit: `ff290289488378ff1b70ebabac66f3fefa0da937`
- Target standard: OWASP ASVS v5.0.0 Level 2
- Scope: source/configuration/migrations/tests/CI. No production or third-party systems were tested.

## Implemented on this branch

- Hardened staff login intended-redirect validation to reject schemes, hosts, protocol-relative paths, backslashes and control characters.
- Restricted patient edits and full mobile-number reveal to admin/reception; practitioners retain masked access to assigned patients and can complete their own appointments. Added endpoint and rendered-control regression coverage.
- Hardened staff session freshness middleware to revoke an authenticated session immediately when its staff account is deactivated; added a regression test.
- Changed session cookie Secure default to true outside local/testing; retained HttpOnly and SameSite=Lax.
- Added regression tests for malicious intended redirects and Secure/HttpOnly/SameSite cookie flags.
- Added patient search SQL-looking input and hostile mass-assignment regression tests.
- Added Composer lockfile audit and npm lockfile audit to CI.
- Added Gitleaks full-history secret scan to CI using a full checkout.
- Added threat model, ASVS checklist, preliminary privacy impact assessment, data inventory/retention register, third-party flow inventory and findings register.
- Marked the old Express/Prisma architecture note as historical/superseded so it cannot be mistaken for the active Laravel architecture.

## Evidence already present on base

- Staff login uses generic errors, password hashing, per-email+IP throttling, session rotation and registry revocation.
- Patient list masks mobile numbers; reveal/update/archive use server-side policies.
- Practitioner patient and appointment access is scoped to assigned appointments.
- Media downloads require admin authorization and a temporary signed URL; upload service checks detected MIME, image decode, dimensions, pixel count, re-encodes and stores privately.
- Public content rendering escapes HTML; existing regression test checks script markup is escaped.
- AI request recording stores approved content IDs and operational metadata, not raw prompts; AI failure logs are intentionally minimal.
- Current AI provider is fake and AI features are disabled by default outside CI. SMS is not configured to a live provider in the assessed environment.
- Existing Task 21 media retention policy is documented and implemented through the media purge service/command path.

## Automated verification status

The new tests and CI scan jobs have been committed to the branch, but **no Task 28 branch CI result is recorded yet**. Do not treat the new tests or scans as passed until GitHub Actions reports success.

Required CI commands include:

```bash
composer audit --locked
npm audit --audit-level=high
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse
npm run lint
npm run typecheck
npm run build
npm run test:browser
```

Gitleaks must complete a full-history scan. Any finding requires investigation and rotation/revocation of the affected credential; deleting it from the current tree is not enough.

## Open release blockers

- **High — retention/privacy policy incomplete:** approved periods and purge behavior are missing for login-attempt hashes, session registry, audit logs, consent records, Smart Smile session/result metadata, AI usage/request rows, queue/failed-job payloads, notification outbox, idempotency snapshots, deletion requests, application logs and backups.
- **Medium — Smart Smile consent token has no expiry:** owner must approve TTL and post-expiry behavior before implementation.
- **Medium — staff idle/absolute session timeout values are unset:** owner policy required; registry expiry currently defaults to ten years if unset.
- **Medium — production controls unverified:** TLS/proxy, database runtime privileges, private storage ACLs, secret injection, backup/log access and retention are deployment evidence not present in the repository.
- **Medium — verification scope incomplete:** full route-by-route IDOR matrix, production-like CSRF negative test, browser DOM-XSS corpus and distributed rate-limit behavior remain open.
- Dependency/secret scan results are pending branch CI.

## Stop condition

Task 28 must not be merged as complete or marked verified while any high finding remains open or required privacy/retention decisions are unapproved. The next action is to obtain the clinic owner's retention/TTL decisions, implement the approved schedules with idempotent purge jobs and tests, then rerun the complete CI/security suite.

No real patient data, patient images, real credentials, production commands, destructive database operations, live AI provider or live SMS provider were used.
