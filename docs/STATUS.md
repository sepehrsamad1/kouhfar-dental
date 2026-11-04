# Project status

Last updated: 2026-11-04 (Task 28 booking OTP lifecycle and security/privacy implementation CI-verified; production blockers remain)

## Task 28 — Security and Privacy Audit

Status: IMPLEMENTATION IN PROGRESS / ACCEPTANCE AND PRODUCTION RELEASE BLOCKED — branch `task/28-security-privacy-audit`. Latest CI run [#1091](https://github.com/sepehrsamad1/kouhfar-dental/actions/runs/41855615836), commit `64d2992d47d461e28538a85f7964c2befbbcf1a2`, completed successfully: 322 Laravel/Pest tests (24,708 assertions), 36 Playwright browser tests, PostgreSQL migrations, Pint, PHPStan, Composer/npm audits, JavaScript lint/typecheck/build, Task 27 deterministic release evaluation and full-history Gitleaks. This does not close owner-policy or deployment blockers. Production use with real patient data remains blocked.

- Target: OWASP ASVS v5.0.0 Level 2 (scoped engineering assessment, not certification).
- Security changes: login redirect validation, production-default Secure session cookies, practitioner patient-edit/mobile-reveal privilege boundary, immediate session revocation on staff deactivation, SQL-looking search input, mass-assignment defenses and Kavenegar SSRF/redirect controls.
- Staff MFA: TOTP required by default for all staff, encrypted TOTP secrets, one-time recovery-code hashes, replay prevention, account throttling, audited enrollment/recovery use and no-store MFA pages. Dedicated feature tests pass. CI disables MFA only for legacy browser screenshot fixtures; production `.env.example` defaults to required.
- Retention implemented: login-attempt hashes 90 days, AI operational request/usage rows 12 months, Idempotency records at 24-hour expiry, and local application logs 30 days. Cleanup is scheduled daily.
- Booking OTP: database-backed challenge lifecycle, public issue/verify endpoints, booking wizard integration, HMAC-only phone/IP/code digests, expiry/attempt/replay protection and atomic one-time consumption with appointment creation are implemented. Owner-approved limits: 5-minute TTL, 5 failed attempts, 60-second resend cooldown, 3 sends/phone/15 minutes, 5 sends/phone/hour, 10 sends/IP/hour. Synthetic tests only. MeliPayamak remains disabled by default; live activation still needs deployment secret injection and separate clinic approval.
- Backup: daily encrypted audit-log backup and monthly authenticated temporary-table restore check are implemented. Separate physical storage, key/ACL provisioning, monitoring and full isolated recovery evidence remain open.
- Smart Smile retention groundwork: `result_generated_at` is recorded for successful output generation; no expiry job or token TTL is enabled.
- Completed-job history groundwork: metadata-only `completed_job_history` and a queue-after listener are committed; no serialized payload, exception text or patient/job arguments are stored. Model contract test was included in successful CI run #979; 7-day purge remains disabled pending owner policy.
- Deletion request workflow groundwork: administrator-only review/closure routes, locked state transitions, allowlisted outcome codes, UTC review/resolution timestamps and audit events are implemented. Closure is administrative disposition only and does not erase referenced records. New migration and feature tests are committed but await a successful CI run.
- Master prompt alignment: `docs/security/task28-master-prompt-alignment.md` records all 19 proposed policies, current evidence, implementation boundaries and owner/legal/deployment blockers. The 12-month Smart Smile final-output choice is a user-selected engineering baseline, not a formal clinic-owner approval.
- Deployment checklist: production TLS/proxy, DB runtime privileges, private storage ACLs, secret injection, backup separation/key ACLs, general distributed throttles and live provider activation are itemized in docs/security/task28-deployment-verification-checklist.md.
- Release blockers: high retention/backup finding SEC-28-003 remains open for unresolved categories and deployment evidence; Smart Smile consent-token TTL; queue/failed-job/outbox/deletion-request retention; MFA lost-device/recovery reset; OTP challenge metadata retention (OD-28-18); production infrastructure and separate-backup-storage evidence; full route-by-route IDOR/CSRF/DOM-XSS/distributed rate-limit verification; supplementary secret-surface review; Kavenegar/MeliPayamak URL-path credential exposure decisions before provider activation.
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
