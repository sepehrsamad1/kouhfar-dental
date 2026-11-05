# Task 28 — Security and Privacy Audit

**Status: LOCAL PROTOTYPE IMPLEMENTATION COMPLETE — CI run #1149 passed on 2026-11-05. Production acceptance/release remains blocked by infrastructure evidence, legal/clinic review and external-provider controls.**

## Task identity

- Task: 28 — Security and Privacy Audit
- Branch: `task/28-security-privacy-audit`
- Latest fully CI-verified revision: `05f307a15e8ce69480253f1bd1bde24d3969f423` (GitHub Actions run [#1149](https://github.com/sepehrsamad1/kouhfar-dental/actions/runs/41949121638), completed successfully on 2026-11-05).
- CI run #1149 passed on the current prototype branch head: full-history Gitleaks, Composer/npm audits, JavaScript lint/typecheck/build, Pint, PHPStan, PostgreSQL migrations, 328 Laravel/Pest tests (24,731 assertions), Task 27 deterministic release evaluation and 36 Playwright browser tests. Composer audit reports no security advisories after updating affected Symfony components to 7.4.23.
- Target standard: OWASP ASVS v5.0.0 Level 2 (scoped engineering assessment, not certification).
- Scope: repository source/configuration/migrations/tests/CI. No production or third-party systems were tested.

## Implemented on this branch

- Notification attempt tracking: added nullable indexed `last_attempt_at`; the sender records UTC attempt time before dispatch and preserves the existing successful `sent_at` behavior. Retry feature test asserts the timestamp. No outbox retention deletion is enabled because the owner decision remains open.
- Completed-job metadata groundwork: added `completed_job_history` with job class, queue name, attempt count and UTC completion time only. A queue-after callback records successful jobs; serialized payloads, exception text and patient/job arguments are deliberately excluded. Model contract test added. CI run #979 includes these commits and passed. Seven-day retention is implemented and covered by synthetic boundary tests.
- Added nullable indexed `result_generated_at` to Smart Smile sessions. Successful simulation completion records a UTC timestamp; upload/retry/failure/revocation clears it. The prototype enforces a 24-hour bearer-token lifetime (deletion remains available after expiry) and clears result JSON after 365 days while preserving consent evidence.
- Hardened staff login intended-redirect validation; restricted patient edits and full mobile reveal to admin/reception; revoked sessions immediately when staff are deactivated.
- Hardened session cookies: Secure outside local/testing, HttpOnly, SameSite=Lax and `__Secure-` production/staging name.
- Added patient search SQL-looking input and hostile mass-assignment regression tests.
- Restricted dormant Kavenegar egress to the approved HTTPS host/path, rejected userinfo/query/fragment/other origins, disabled redirects and prevented transport exceptions from exposing the API-key URL.
- Implemented staff TOTP MFA for all staff accounts by default. TOTP secrets are encrypted at rest; one-time recovery-code hashes are encrypted at rest; recovery codes are displayed once without session persistence; TOTP replay is rejected using a locked per-user counter; MFA attempts are account-throttled; enrollment and recovery-code use are audited; MFA pages use no-store headers.
- Added a 10-minute pending-MFA enrollment/challenge window. Production default is `STAFF_MFA_REQUIRED=true`. CI disables the requirement only for legacy browser screenshot fixtures; dedicated feature tests explicitly enable and exercise MFA.
- Added approved retention cleanup: login-attempt hashes after 90 days, AI request/usage operational rows after 12 months, and Idempotency records at their existing 24-hour expiry. The cleanup command is scheduled daily.
- Configured local daily application log rotation with a 30-day retention window.
- Added a MeliPayamak provider-generated OTP adapter behind `OtpProviderManager`. It uses a fixed HTTPS host, redirects disabled, TLS verification enabled, no automatic retries, and a deployment-only token. It remains disabled by default. The booking challenge lifecycle and approved limits are now implemented; live sending remains blocked pending deployment secret injection and separate clinic activation approval.
- Added Laravel-native audit-log backup commands using a consistent database snapshot, AES-256-GCM authenticated chunk encryption, per-backup salt/key derivation, an explicitly configured absolute backup path, 90-day minimum backup-copy retention, and no database-specific dump utility.
- Scheduled encrypted audit-log backups daily at 02:00 Asia/Tehran and authenticated temporary-table restore verification monthly at 04:00 on day 1. This is not a full isolated disaster-recovery exercise.
- Added Composer/npm dependency audits and full-history Gitleaks to CI, plus threat model, ASVS checklist, privacy impact assessment, data inventory/retention register, third-party flow inventory, findings register, owner decision register and operational runbooks.
- Deletion request workflow groundwork: added review and closure metadata, active-admin-only web endpoints, transaction-locked state transitions, allowlisted reason codes and audit events. Closing a request records a non-destructive disposition only; it does not delete patient/CRM records or media.

## Prototype defaults and owner decisions recorded

The user delegated the remaining engineering defaults for a local synthetic-data prototype. The complete temporary baseline is recorded in [the owner-decision register](security/task28-owner-decisions.md). Key values now implemented or recorded:

- Consent evidence: six-year planning baseline; no automatic deletion in this prototype.
- Smart Smile token: 24 hours; expiry blocks upload/status reads, but the original token can still request deletion.
- Smart Smile result: 365 days; result payload is cleared without deleting consent evidence. Raw input media remains 90 days.
- Completed-job metadata: 7 days; failed queue records: 30 days.
- Sent notification outbox metadata: 12 months; stale failed notification metadata: 30 days.
- Deletion request records: 3 years after closure/rejection; pending requests and legal holds are preserved. Handling target: 30 days.
- Terminal OTP challenge metadata: 30 days; active challenges remain.
- Staff/practitioner profile after deactivation: 90-day planning default, not automated without a reliable deactivation timestamp.
- Patient-match flags: unresolved until human review; resolved flags target 90 days, but no automatic merge or purge is enabled without a reviewed lifecycle.
- Backups: daily, 35-day rolling planning target for future full DB/files backup; RPO <=24 hours, RTO <=8 hours. Current code backs up audit logs only.
- Lost MFA device: controlled admin identity verification, revoke sessions, reset factor, re-enroll, issue new one-time recovery codes and audit the action. Runbook only; no self-service reset UI.
- MeliPayamak, Kavenegar and live AI remain disabled.

These are engineering defaults for synthetic local testing, not universal legal standards or production approval.

## Automated verification status

GitHub Actions run [#1149](https://github.com/sepehrsamad1/kouhfar-dental/actions/runs/41949121638), commit `05f307a15e8ce69480253f1bd1bde24d3969f423`, completed successfully on 2026-11-05:
- 328 Laravel/Pest tests passed (24,731 assertions), including Smart Smile expiry/deletion, terminal OTP retention, completed/failed job retention, notification outbox retention and deletion-request/legal-hold boundaries.
- 36 Playwright browser tests passed.
- PostgreSQL migrations, Pint and PHPStan passed.
- Composer audit and npm audit passed; Composer reports no security advisories after the Symfony 7.4.23 patch updates.
- JavaScript lint, typecheck and production asset build passed.
- Task 27 deterministic release evaluation passed.
- Full-history Gitleaks scan passed.

This is CI verification of the local prototype code only. It does not verify production TLS/proxy, external provider logs, secret injection, storage/backup ACLs, full DB/files backup or isolated recovery.

## Remaining production release blockers

- **High — SEC-28-003:** prototype retention defaults and cleanup are implemented and CI-verified. Full DB/files backup, separate physical backup storage, key/ACL provisioning, monitoring and a full isolated recovery exercise remain unverified. Consent evidence is not automatically purged.
- **Medium — SEC-28-006:** production TLS/proxy, database runtime privileges, private storage ACLs, secret injection and log/backup access controls require deployment evidence.
- **Medium — SEC-28-008:** full route-by-route IDOR matrix, production-like CSRF negative tests, DOM-XSS corpus and distributed rate-limit behavior remain open.
- **Medium — SEC-28-014 / SEC-28-015:** Kavenegar and MeliPayamak credentials remain in URL paths. Both live adapters stay disabled pending provider/network log review and explicit clinic approval or a header-auth alternative.
- **MFA recovery:** controlled lost-authenticator identity-proofing remains an operational rollout requirement.
- **Staff profiles / patient-match flags:** 90-day defaults are documented but not automated until reliable deactivation/review lifecycle evidence exists.
- No live AI, SMS, production or third-party testing was performed.

## Stop condition

Local prototype implementation is complete and CI-verified. Do not mark the application production-ready or enable real patient data/live providers until the remaining release blockers, legal/clinic review and deployment evidence are closed.

No real patient data, patient images, real credentials, production commands, destructive database operations, live AI provider or live SMS provider were used.

## Booking OTP lifecycle — owner-approved policy (2026-11-04)

- Implemented a database-backed challenge lifecycle and public issue/verify endpoints, integrated with the booking request and existing booking wizard.
- Policy values explicitly approved by the user: 5-minute challenge TTL; 5 failed attempts; 60-second resend cooldown; 3 sends per phone per 15 minutes; 5 sends per phone per hour; 10 sends per IP per hour.
- The database stores HMAC digests only for the normalized phone, client IP and provider-generated code. It does not store plaintext OTP values. The code digest is cleared after successful verification and terminal expiry, exhaustion or invalidation.
- PostgreSQL advisory locks serialize both phone and IP quotas across application nodes. New issuance invalidates prior active challenges. A verified challenge is consumed under row lock in the same transaction as appointment creation.
- Added synthetic-only feature coverage for success, wrong code, expiry, attempt exhaustion, resend cooldown, phone/IP quotas, invalidation, replay/consumption and provider failure; added browser API contract coverage.
- SMS_OTP_DRIVER remains `null` by default. MeliPayamak remains disabled pending deployment secret injection and separate clinic approval. No live SMS requests were made.
- Terminal OTP challenge metadata is pruned after 30 days; active challenges are preserved. This is a prototype engineering default, not a legal retention approval.
- Production TLS/proxy, DB privileges, storage ACLs, secret injection, backup separation/key ACLs, general distributed throttles and live provider activation are tracked in [the deployment verification checklist](security/task28-deployment-verification-checklist.md).

## Prototype retention and lifecycle defaults (2026-11-05)

The user delegated the remaining engineering defaults for the local prototype. The following implementation was added on the Task 28 branch and is awaiting CI verification:

- Smart Smile token lifetime is 24 hours. Expired tokens cannot upload or read status; the original token remains able to request deletion after expiry. Consent/session evidence is retained.
- Smart Smile result payloads expire after 365 days. Cleanup clears only the result JSON; it does not delete the session or consent evidence. Raw Smart Smile input media continues to use the existing 90-day policy.
- Successful-job metadata is retained 7 days; failed queue records 30 days. Running/pending jobs and batch records are not age-pruned.
- Sent notification outbox rows are retained 12 months; stale failed notification rows 30 days after last attempt.
- Closed/rejected deletion request rows are retained 3 years; pending and under-review requests are preserved. Patient/CRM records and original audit logs are not deleted.
- Terminal booking OTP challenge metadata is retained 30 days; active challenges are preserved.
- Existing 90-day login hash, 12-month AI operational metadata, 24-hour idempotency and 30-day local log policies remain.
- Consent evidence uses a provisional six-year planning baseline but is not automatically purged. Staff-profile 90-day and resolved patient-match 90-day periods remain documented planning defaults only because the current schema/workflow lacks safe deactivation/review lifecycle evidence.

Added migration 2026_11_05_000001_add_task28_smart_smile_expiry.php, bounded cleanup logic and synthetic boundary tests. These changes are not yet CI-verified at this point in the document. See security/task28-owner-decisions.md for the complete policy and prototype/production boundary.

Important: the periods above are not universal legal standards. They are temporary local-prototype engineering defaults selected by the user. Real patient data, production deployment and live provider activation remain blocked pending legal/clinic review and deployment evidence.
