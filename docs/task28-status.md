# Task 28 — Security and Privacy Audit

**Status: IMPLEMENTATION IN PROGRESS — acceptance and production release remain blocked by SEC-28-003, open owner decisions and deployment/verification evidence.**

## Task identity

- Task: 28 — Security and Privacy Audit
- Branch: `task/28-security-privacy-audit`
- Last fully CI-verified revision: `3282ab38d55df8d3b5cd7cb01a70ed6aada4821c` (GitHub Actions run [#979](https://github.com/sepehrsamad1/kouhfar-dental/actions/runs/41684593114), completed successfully).
- Latest completed CI revision: `e6288cd8ffe43f77d90f9d99bc8a895f2ea2d41a` (run [#1012](https://github.com/sepehrsamad1/kouhfar-dental/actions/runs/41701925876)); Gitleaks, Composer/npm audits and JavaScript lint/typecheck/build passed. Pint failed on `DeletionRequestWorkflowTest.php` with `fully_qualified_strict_types` and `ordered_imports`; PHPStan, migrations, Laravel tests and browser tests were skipped, not failed. Commit `f217378d2218a13991c8ff974f471680908f0499` restores the `App\\StaffUser` import for static factory calls while keeping the return type fully-qualified; awaiting CI.
- Target standard: OWASP ASVS v5.0.0 Level 2 (scoped engineering assessment, not certification).
- Scope: repository source/configuration/migrations/tests/CI. No production or third-party systems were tested.

## Implemented on this branch

- Notification attempt tracking: added nullable indexed `last_attempt_at`; the sender records UTC attempt time before dispatch and preserves the existing successful `sent_at` behavior. Retry feature test asserts the timestamp. No outbox retention deletion is enabled because the owner decision remains open.
- Completed-job metadata groundwork: added `completed_job_history` with job class, queue name, attempt count and UTC completion time only. A queue-after callback records successful jobs; serialized payloads, exception text and patient/job arguments are deliberately excluded. Model contract test added. CI run #979 includes these commits and passed. The proposed 7-day retention is not activated while the owner decision remains open.
- Added nullable indexed `result_generated_at` to Smart Smile sessions. Successful simulation completion records a UTC timestamp; upload/retry/failure/revocation clears it. This is preparatory metadata only: no 12-month purge or consent-token TTL is active.
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

## Owner decisions recorded

- Patient/CRM records and original audit logs: indefinite retention; no automatic deletion.
- Staff idle/absolute session timeouts: 15 minutes / 8 hours.
- Login-attempt hashes: 90 days.
- Application logs: local, 30 days.
- AI operational request/usage records: 12 months.
- Idempotency records: delete after the existing 24-hour expiry.
- Staff MFA: TOTP plus one-time recovery codes.
- MeliPayamak: purchased provider for backend OTP delivery; its token is a deployment secret.
- Consent evidence retention and Smart Smile token TTL remain undecided. The user selected 12 months for the final Smart Smile output as an engineering baseline, and the code records its generation time; automated expiry remains disabled pending formal owner/legal policy reconciliation. Queue/failed-job payloads, notification outbox and deletion-request retention remain undecided. Metadata-only successful-job history is CI-verified in run #979; no retention purge is enabled.

See [clinic-owner decisions](security/task28-owner-decisions.md).

## Automated verification status

Revision `3282ab38d55df8d3b5cd7cb01a70ed6aada4821c` passed CI run #979:
- 302 Laravel/Pest tests passed (24,617 assertions).
- 36 Playwright browser tests passed.
- PostgreSQL migrations passed.
- Pint formatting check and PHPStan static analysis passed.
- Composer dependency audit and npm audit passed.
- JavaScript lint, typecheck and production asset build passed.
- Task 27 deterministic release evaluation passed.
- Full-history Gitleaks secret scan passed.

Run #1012 passed Gitleaks, Composer/npm audits and JavaScript lint/typecheck/build, then failed Pint on `DeletionRequestWorkflowTest.php` with `fully_qualified_strict_types` and `ordered_imports`. PHPStan, migrations, Laravel tests and browser tests were skipped, not failed. Commit `f217378d2218a13991c8ff974f471680908f0499` restores the `App\\StaffUser` import used by static factory calls while retaining the fully-qualified return type; it awaits CI verification.

## Open release blockers

- **High — SEC-28-003 retention/backup controls incomplete:** approved cleanup is implemented for login-attempt hashes, AI operational rows and Idempotency records. Consent evidence retention and token TTL remain undecided; Smart Smile output expiry is not yet automated. Queue/failed-job payloads, notification outbox/error metadata and deletion requests remain undecided. Separate physical backup storage, encryption-key/ACL provisioning, monitoring and a full isolated database recovery exercise remain deployment gates. Original audit logs and patient/CRM records must not be automatically deleted.
- **Medium — SEC-28-004 Smart Smile consent token:** TTL and post-expiry/revocation behavior still need owner approval.
- **Booking OTP deployment gate:** the challenge lifecycle, booking UI integration and owner-approved limits are implemented with synthetic tests. No live provider call has been made. Keep `SMS_OTP_DRIVER=null` until deployment secrets and clinic activation are separately approved. OTP challenge metadata retention remains open as OD-28-18; provider URL-path log exposure remains SEC-28-015.
- **Medium — MFA recovery operations:** enrollment and one-time recovery codes are implemented, but a controlled, audited lost-authenticator/recovery-code reset process and identity-proofing procedure must be defined before staff rollout.
- **Medium — SEC-28-006 production controls:** TLS/proxy, database runtime privileges, private storage ACLs, secret injection, backup/log access and retention need deployment evidence.
- **Medium — SEC-28-008 verification scope:** full route-by-route IDOR matrix, production-like CSRF negative tests, browser DOM-XSS corpus and distributed rate-limit behavior remain open.
- **Medium — SEC-28-014 Kavenegar / SEC-28-015 MeliPayamak:** both provider credentials are carried in URL paths. Keep both live adapters disabled until provider/network access-log retention and redaction are reviewed and the clinic approves residual exposure or selects a header-auth provider.
- **Medium — supplementary secret review:** automated Gitleaks and dependency audits passed; review GitHub secret alerts and external log/artifact surfaces separately.
- **Open owner decision — OD-28-10:** staff/practitioner profile retention and patient-match flag policy.

## Stop condition

Do not merge Task 28 as complete or mark it production-ready while SEC-28-003 or other release blockers remain open, or while required privacy/retention decisions and deployment evidence are outstanding.

No real patient data, patient images, real credentials, production commands, destructive database operations, live AI provider or live SMS provider were used.

## Booking OTP lifecycle — owner-approved policy (2026-11-04)

- Implemented a database-backed challenge lifecycle and public issue/verify endpoints, integrated with the booking request and existing booking wizard.
- Policy values explicitly approved by the user: 5-minute challenge TTL; 5 failed attempts; 60-second resend cooldown; 3 sends per phone per 15 minutes; 5 sends per phone per hour; 10 sends per IP per hour.
- The database stores HMAC digests only for the normalized phone, client IP and provider-generated code. It does not store plaintext OTP values. The code digest is cleared after successful verification and terminal expiry, exhaustion or invalidation.
- PostgreSQL advisory locks serialize both phone and IP quotas across application nodes. New issuance invalidates prior active challenges. A verified challenge is consumed under row lock in the same transaction as appointment creation.
- Added synthetic-only feature coverage for success, wrong code, expiry, attempt exhaustion, resend cooldown, phone/IP quotas, invalidation, replay/consumption and provider failure; added browser API contract coverage.
- SMS_OTP_DRIVER remains `null` by default. MeliPayamak remains disabled pending deployment secret injection and separate clinic approval. No live SMS requests were made.
- **Open owner decision OD-28-18:** retention duration for OTP challenge metadata. No automatic purge is enabled until approved.
