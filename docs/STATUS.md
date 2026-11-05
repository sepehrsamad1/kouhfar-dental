# Project status

Last updated: 2026-11-05 (Task 28 local-prototype implementation CI-verified in run #1149; production blockers remain)

## Task 28 — Security and Privacy Audit

Status: LOCAL SYNTHETIC-DATA PROTOTYPE IMPLEMENTATION COMPLETE; MeliPayamak local activation path prepared; latest SMS follow-up CI pending. Production acceptance/release remains blocked. Branch: `task/28-security-privacy-audit`; PR #30 remains open and unmerged.

- Baseline code verification: [CI run #1149](https://github.com/sepehrsamad1/kouhfar-dental/actions/runs/41949121638), commit `05f307a15e8ce69480253f1bd1bde24d3969f423`: 328 Laravel/Pest tests (24,731 assertions), 36 Playwright tests, PostgreSQL migrations, Pint, PHPStan, Composer/npm audits, JavaScript lint/typecheck/build, Task 27 evaluation and full-history Gitleaks passed.
- Security work includes session-cookie hardening, authorization fixes, staff TOTP MFA/recovery codes, rate limits, safe provider egress, data-retention cleanup, Smart Smile token/result expiry, encrypted audit-log backups, threat model, ASVS checklist, privacy impact assessment, data inventory, third-party flow inventory, findings and deployment runbooks.
- Booking OTP lifecycle is implemented: provider-generated MeliPayamak code, five-minute challenge TTL, five failed attempts, one-time verification/consumption, 60-second resend cooldown, three sends per phone/15 minutes, five per phone/hour and ten per IP/hour. Phone, IP and OTP are stored only as keyed HMAC digests. Provider response `status` errors are rejected even if a `code` is present; the OTP is never returned to the browser or logged.
- MeliPayamak local setup is documented in `docs/security/melipayamak-otp-runbook.md`. The user authorized controlled local prototype testing. The committed default remains `SMS_OTP_DRIVER=null`; CI never sends SMS. The provider token belongs only in the ignored local `.env`, never in Git. No live SMS was sent by this engineering change.
- The local prototype defaults to SQLite/file cache. OTP quota locking now supports local SQLite through file-cache locks; PostgreSQL uses transaction-scoped advisory locks for multi-node operation. SQLite is single-machine prototype-only, not the production multi-node database.
- Kavenegar transactional SMS and live AI remain disabled. MeliPayamak production use remains gated by clinic/legal vendor approval and provider-side URL-path access-log review.
- Production blockers: full DB/files backup and isolated restore; separate backup storage/key/ACLs/monitoring; TLS/proxy, DB/storage/secrets/logging evidence; complete route-by-route IDOR/CSRF/DOM-XSS/distributed-throttle tests; provider URL-path log review; and operational lost-authenticator identity-proofing.
- Retention periods in this task are engineering defaults for synthetic local testing, not universal legal standards. Do not use real patient data or enable production providers until the remaining review and deployment gates are closed.
- Do not merge PR #30 to `main` or mark the application production-ready without explicit owner authorization and closure of production blockers.

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
