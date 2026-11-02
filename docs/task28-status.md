# Task 28 — Security and Privacy Audit

**Status: BRANCH IMPLEMENTATION CI-VERIFIED — TASK ACCEPTANCE AND PRODUCTION RELEASE REMAIN BLOCKED by SEC-28-003 and SEC-28-012, other open findings, and deployment evidence.**

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
- Added production/staging `__Secure-` session-cookie naming to satisfy ASVS V3.3.1; the configuration regression test passed in CI run #843.
- Added ASVS Level 2 MFA finding: staff authentication is currently password-only; owner must approve the second factor and recovery flow before this can be implemented.
- Recorded owner-approved patient/CRM and original audit-log indefinite retention; no automatic deletion of these records is authorized by the owner decision.
- Added Laravel-native audit-log backup commands using a consistent database snapshot, AES-256-GCM authenticated chunk encryption, an explicitly configured absolute backup path, 90-day minimum backup-copy retention, and no database-specific dump utility.
- Scheduled encrypted audit-log backups daily at 02:00 Asia/Tehran and temporary-table restore verification monthly at 04:00 on day 1. Added an operational runbook and regression tests for encryption, tamper detection, missing configuration and backup-copy retention.
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

Latest branch commit `57d442a9f4267901a4be4f3cd73792bb7f2fbb03` passed [GitHub Actions CI run #886](https://github.com/sepehrsamad1/kouhfar-dental/actions/runs/41527012433).

- 286 Laravel/Pest tests passed (24,520 assertions).
- 36 Playwright browser tests passed.
- PostgreSQL migrations passed.
- `composer audit --locked` passed with no reported advisories.
- `npm audit --audit-level=high` passed; npm reported zero vulnerabilities.
- Pint formatting check and PHPStan static analysis passed.
- JavaScript lint, typecheck and production asset build passed.
- Full-history Gitleaks secret scan passed.
- Responsive screenshot artifacts for Tasks 12, 16, 19, 20, 26 and 27, plus the Task 27 AI evaluation report, were uploaded successfully.

This verifies the source revision at commit `57d442a9f4267901a4be4f3cd73792bb7f2fbb03` under the automated CI environment and synthetic test data. It is not a production penetration test, live Kavenegar/AI/SMS provider test, GitHub secret-alert review, independent infrastructure audit or full disaster-recovery exercise. The monthly temporary-table restore check is a controlled integrity/restore check, not a full isolated database recovery drill.

Gitleaks scans the full repository history. Any future finding requires investigation and credential rotation/revocation, not merely deleting the current file.

## Open release blockers

- **High — staff MFA missing (ASVS V6.3.3):** staff login currently uses only email/password. The clinic owner must approve the second factor and recovery policy; Task 15 provider integration is not activated.
- **High — retention/backup controls incomplete:** patient/CRM records and original audit logs are owner-approved for indefinite retention and are not automatically purged. The backup pipeline and schedule are implemented in this branch, but separate physical storage, key/ACL provisioning, monitoring and operational recovery evidence are not yet supplied. Retention for login-attempt hashes, consent/Smart Smile metadata, AI usage/request rows, queue/failed-job payloads, notification outbox, idempotency snapshots, deletion requests and application logs remains partly open. See [clinic-owner decisions](security/task28-owner-decisions.md) and the [backup runbook](security/audit-log-backup-runbook.md).
- **Medium — Kavenegar API key remains in the provider URL path:** the adapter is dormant and outbound host/redirect controls are hardened, but provider/network log redaction or an alternative header-auth provider must be approved before activation.
- **Medium — Smart Smile consent token has no expiry:** owner must approve TTL and post-expiry behavior before implementation.
- **Medium — staff idle/absolute session timeout values:** owner approved 15-minute idle and 8-hour absolute limits; configuration defaults are set in this branch. Verify production configuration and registry behavior at deployment.
- **Medium — production controls unverified:** TLS/proxy, database runtime privileges, private storage ACLs, secret injection, backup/log access and retention are deployment evidence not present in the repository.
- **Medium — verification scope incomplete:** full route-by-route IDOR matrix, production-like CSRF negative test, browser DOM-XSS corpus and distributed rate-limit behavior remain open.
- **CI scans passed:** Composer/npm audits and full-history Gitleaks passed in run #886. Supplementary review of GitHub secret alerts, binary images/artifacts and external log sinks remains open.

## Stop condition

Task 28 must not be merged as complete or marked verified while any high finding remains open or required privacy/retention decisions are unapproved. The next actions are to resolve the remaining owner decisions, approve and implement staff MFA, provision the separate backup volume and encryption key, collect operational backup/recovery evidence, and close the remaining medium findings using deployment evidence and a route-by-route verification matrix.

No real patient data, patient images, real credentials, production commands, destructive database operations, live AI provider or live SMS provider were used.
