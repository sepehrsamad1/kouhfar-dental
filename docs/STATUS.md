# Project status

Last updated: 2026-11-03 (Task 28 deletion-request workflow and CI formatting follow-up)

## Task 28 — Security and Privacy Audit

Status: IMPLEMENTATION IN PROGRESS / ACCEPTANCE AND PRODUCTION RELEASE BLOCKED — branch `task/28-security-privacy-audit`. The prior CI run #979 passed on revision `3282ab38d55df8d3b5cd7cb01a70ed6aada4821c`. The newer CI run [#994](https://github.com/sepehrsamad1/kouhfar-dental/actions/runs/41687964747) passed full-history Gitleaks, Composer/npm audits, JavaScript lint/typecheck/build, but failed the PHP Pint formatting check on the deletion-request controller and feature test. PHPStan, migrations, Laravel tests and browser tests were skipped after that failure. Formatting corrections are committed separately; no new CI verification is claimed yet. This does not close owner-policy or deployment blockers. Production use with real patient data remains blocked.

- Target: OWASP ASVS v5.0.0 Level 2 (scoped engineering assessment, not certification).
- Security changes: login redirect validation, production-default Secure session cookies, practitioner patient-edit/mobile-reveal privilege boundary, immediate session revocation on staff deactivation, SQL-looking search input, mass-assignment defenses and Kavenegar SSRF/redirect controls.
- Staff MFA: TOTP required by default for all staff, encrypted TOTP secrets, one-time recovery-code hashes, replay prevention, account throttling, audited enrollment/recovery use and no-store MFA pages. Dedicated feature tests pass. CI disables MFA only for legacy browser screenshot fixtures; production `.env.example` defaults to required.
- Retention implemented: login-attempt hashes 90 days, AI operational request/usage rows 12 months, Idempotency records at 24-hour expiry, and local application logs 30 days. Cleanup is scheduled daily.
- MeliPayamak OTP adapter: added and tested with fake HTTP responses; disabled by default. The booking OTP challenge lifecycle is not implemented, so booking remains fail-closed until expiry/attempt/resend policy is approved.
- Backup: daily encrypted audit-log backup and monthly authenticated temporary-table restore check are implemented. Separate physical storage, key/ACL provisioning, monitoring and full isolated recovery evidence remain open.
- Smart Smile retention groundwork: `result_generated_at` is recorded for successful output generation; no expiry job or token TTL is enabled.
- Completed-job history groundwork: metadata-only `completed_job_history` and a queue-after listener are committed; no serialized payload, exception text or patient/job arguments are stored. Model contract test was included in successful CI run #979; 7-day purge remains disabled pending owner policy.
- Deletion request workflow groundwork: administrator-only review/closure routes, locked state transitions, allowlisted outcome codes, UTC review/resolution timestamps and audit events are implemented. Closure is administrative disposition only and does not erase referenced records. New migration and feature tests are committed but await a successful CI run.
- Master prompt alignment: `docs/security/task28-master-prompt-alignment.md` records all 19 proposed policies, current evidence, implementation boundaries and owner/legal/deployment blockers. The 12-month Smart Smile final-output choice is a user-selected engineering baseline, not a formal clinic-owner approval.
- Release blockers: high retention/backup finding SEC-28-003 remains open for unresolved categories and deployment evidence; Smart Smile consent-token TTL; queue/failed-job/outbox/deletion-request retention; MFA lost-device/recovery reset; booking OTP expiry/attempt/resend policy; production infrastructure and separate-backup-storage evidence; full route-by-route IDOR/CSRF/DOM-XSS/distributed rate-limit verification; supplementary secret-surface review; Kavenegar/MeliPayamak URL-path credential exposure decisions before provider activation.
- Do not merge Task 28 as complete or mark it verified for production until all high findings are closed and required privacy/retention decisions are approved.

## Task 27 — AI evaluation, safety gates, dental knowledge and cost controls

Status: CI-VERIFIED on branch `task/27-ai-evaluation-cost-safety`. Production activation remains gated.

Latest verification: [GitHub Actions run #778](https://github.com/sepehrsamad1/kouhfar-dental/actions/runs/41436715873), commit `02737dd47274f990dd7e297979fb46c00c9f3973`.

- 262 Laravel/Pest tests passed (24,432 assertions).
- All 200 owner-approved evaluation cases passed: 100 Persian and 100 English.
- Evaluation metrics: citation presence 100%, grounding 100%, refusal correctness 100%, emergency response 100%, forbidden-content prevention 100%.
- All 36 Playwright browser tests passed.
- AI admin responsive checks passed at 375px, 768px and 1440px; screenshots were uploaded as CI artifacts.
- JavaScript lint/type-check/build, Pint, PHPStan and PostgreSQL migrations passed.

Release boundary: this is deterministic testing with the fake provider, not proof of live-model clinical accuracy. The 100 bilingual FAQ entries remain unpublished drafts pending clinician review. No real AI provider, credentials, provider billing telemetry, patient data or production service was connected. AI remains disabled by default.

Detailed implementation, commands, evaluation evidence, security notes and remaining production gates: [docs/task27-status.md](task27-status.md).
