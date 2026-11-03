# Project status

Last updated: 2026-11-03 (Task 28 completed-job metadata groundwork added)

## Task 28 — Security and Privacy Audit

Status: IMPLEMENTATION CI-VERIFIED / TASK ACCEPTANCE AND PRODUCTION RELEASE BLOCKED — branch `task/28-security-privacy-audit`; CI run [#968](https://github.com/sepehrsamad1/kouhfar-dental/actions/runs/41655203664) passed on code revision `f3b91292bede46ce0e82c6e30291adb78a606e9b`; subsequent completed-job metadata commits await a new CI run. Production use with real patient data remains blocked.

- Target: OWASP ASVS v5.0.0 Level 2 (scoped engineering assessment, not certification).
- Security changes: login redirect validation, production-default Secure session cookies, practitioner patient-edit/mobile-reveal privilege boundary, immediate session revocation on staff deactivation, SQL-looking search input, mass-assignment defenses and Kavenegar SSRF/redirect controls.
- Staff MFA: TOTP required by default for all staff, encrypted TOTP secrets, one-time recovery-code hashes, replay prevention, account throttling, audited enrollment/recovery use and no-store MFA pages. Dedicated feature tests pass. CI disables MFA only for legacy browser screenshot fixtures; production `.env.example` defaults to required.
- Retention implemented: login-attempt hashes 90 days, AI operational request/usage rows 12 months, Idempotency records at 24-hour expiry, and local application logs 30 days. Cleanup is scheduled daily.
- MeliPayamak OTP adapter: added and tested with fake HTTP responses; disabled by default. The booking OTP challenge lifecycle is not implemented, so booking remains fail-closed until expiry/attempt/resend policy is approved.
- Backup: daily encrypted audit-log backup and monthly authenticated temporary-table restore check are implemented. Separate physical storage, key/ACL provisioning, monitoring and full isolated recovery evidence remain open.
- CI evidence: 302 Laravel/Pest tests passed (24,617 assertions); 36 Playwright browser tests passed; PostgreSQL migrations, Pint, PHPStan, JavaScript lint/typecheck/build, Composer audit, npm audit and full-history Gitleaks passed. Task 27 deterministic evaluation passed.
- Smart Smile retention groundwork: `result_generated_at` is now recorded for successful output generation; no expiry job or token TTL is enabled. See [retention implementation review](security/task28-retention-implementation-review.md).
- Completed-job history groundwork: metadata-only `completed_job_history` and a queue-after listener are committed; no serialized payload, exception text or patient/job arguments are stored. Model contract test added. Latest CI run predates these commits, so CI verification is pending; 7-day purge remains disabled pending owner policy.
- Master prompt alignment: [policy-by-policy execution register](security/task28-master-prompt-alignment.md) records all 19 proposed policies, current evidence, implementation boundaries and owner/legal/deployment blockers. The 12-month Smart Smile final-output choice from the current conversation is recorded as a user-selected engineering baseline, not a formal clinic-owner approval.
- Artifacts: responsive screenshot sets for Tasks 12, 16, 19, 20, 26 and 27, plus Task 27 AI evaluation report, were uploaded. See [Task 28 status](task28-status.md), [ASVS checklist](security/task28-asvs-checklist.md), [full ASVS requirement register](security/task28-asvs-v5.0.0-level2-register.csv), [route authorization matrix](security/task28-route-authorization-matrix.md), [threat model](security/task28-threat-model.md), [privacy impact assessment](security/task28-privacy-impact-assessment.md), [data inventory](security/task28-data-inventory.md), [third-party flows](security/task28-third-party-flows.md), [findings register](security/task28-findings.md), [clinic-owner decisions](security/task28-owner-decisions.md), [staff MFA runbook](security/staff-mfa-runbook.md) and [MeliPayamak OTP runbook](security/melipayamak-otp-runbook.md).
- Release blockers: high retention/backup finding SEC-28-003 remains open for unresolved categories and deployment evidence; Smart Smile consent-token TTL; queue/failed-job/outbox/deletion-request retention; MFA lost-device/recovery reset procedure; booking OTP expiry/attempt/resend policy; production infrastructure and separate-backup-storage evidence; full route-by-route IDOR/CSRF/DOM-XSS/distributed rate-limit verification; supplementary secret-surface review; Kavenegar/MeliPayamak URL-path credential exposure decision before provider activation.
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
