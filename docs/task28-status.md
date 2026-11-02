# Task 28 — Security and Privacy Audit

**Status: CURRENT BRANCH CI-PASSED; TASK ACCEPTANCE BLOCKED — CI run #843 passed on commit `38e412e4e7bd8cea2bc3a5635aa22879d541c9f1`. Task 28 remains blocked by high findings SEC-28-003 (retention/purge) and SEC-28-012 (staff MFA), plus owner/deployment decisions and open verification scope.**

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
- Restricted dormant Kavenegar egress to the approved HTTPS host/path, rejected userinfo/query/fragment/other origins, disabled redirects and prevented transport exceptions from exposing the API-key URL; added synthetic SSRF/redirect regression tests.
- Added production/staging `__Secure-` session-cookie naming to satisfy ASVS V3.3.1; the new configuration regression test awaits CI retest.
- Added ASVS Level 2 MFA finding: staff authentication is currently password-only; owner must approve the second factor and recovery flow before this can be implemented.
- Added Composer lockfile audit and npm lockfile audit to CI.
- Added Gitleaks full-history secret scan to CI using a full checkout.
- Added threat model, ASVS checklist, preliminary privacy impact assessment, data inventory/retention register, third-party flow inventory, findings register and clinic-owner decision register.
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

[CI run #843](https://github.com/sepehrsamad1/kouhfar-dental/actions/runs/41480042377) passed on code commit `38e412e4e7bd8cea2bc3a5635aa22879d541c9f1`.

- 280 Laravel/Pest tests passed (24,493 assertions), including the new patient authorization, staff deactivation and mocked Kavenegar egress regression tests.
- 36 Playwright browser tests passed.
- PostgreSQL migrations passed.
- `composer audit --locked` passed with no reported advisories.
- `npm audit --audit-level=high` passed; npm reported zero vulnerabilities.
- Pint, PHPStan, JavaScript lint, typecheck and production build passed.
- Full-history Gitleaks scan passed.

This proves the code revision at commit `653e6d45e960edf391d97d0d1b63030daf4a3399` and its synthetic tests only. The latest branch commit includes the `__Secure-` cookie-prefix change and its passing regression test. It is not a production penetration test, live Kavenegar/AI provider test, GitHub secret-alert review, or infrastructure verification.

Required CI commands are recorded above. Gitleaks scans the full repository history; any future finding requires investigation and credential rotation/revocation, not merely deleting the current file.

## Open release blockers

- **High — staff MFA missing (ASVS V6.3.3):** staff login currently uses only email/password. The clinic owner must approve the second factor and recovery policy; Task 15 provider integration is not activated.
- **High — retention/privacy policy incomplete:** the 2-year clinical-record and Task 21 media periods remain recorded; however, retention/purge behavior for login-attempt hashes, session registry, audit logs, consent records, Smart Smile session/result metadata, AI usage/request rows, queue/failed-job payloads, notification outbox, idempotency snapshots, deletion requests, application logs and backups is still unapproved or unimplemented. See [clinic-owner decisions](security/task28-owner-decisions.md).
- **Medium — Kavenegar API key remains in the provider URL path:** the adapter is dormant and outbound host/redirect controls are hardened, but provider/network log redaction or an alternative header-auth provider must be approved before activation.
- **Medium — Smart Smile consent token has no expiry:** owner must approve TTL and post-expiry behavior before implementation.
- **Medium — staff idle/absolute session timeout values are unset:** owner policy required; registry expiry currently defaults to ten years if unset.
- **Medium — production controls unverified:** TLS/proxy, database runtime privileges, private storage ACLs, secret injection, backup/log access and retention are deployment evidence not present in the repository.
- **Medium — verification scope incomplete:** full route-by-route IDOR matrix, production-like CSRF negative test, browser DOM-XSS corpus and distributed rate-limit behavior remain open.
- **CI scans passed:** Composer/npm audits and full-history Gitleaks passed in run #820. Supplementary review of GitHub secret alerts, binary images/artifacts and external log sinks remains open.

## Stop condition

Task 28 must not be merged as complete or marked verified while any high finding remains open or required privacy/retention decisions are unapproved. The next action is to obtain the clinic owner's decisions in [task28-owner-decisions.md](security/task28-owner-decisions.md), implement the approved MFA and retention/purge controls with regression tests, and close the remaining medium findings using deployment evidence and a route-by-route verification matrix.

No real patient data, patient images, real credentials, production commands, destructive database operations, live AI provider or live SMS provider were used.
