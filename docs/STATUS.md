# Project status

Last updated: 2026-11-02

## Task 28 — Security and Privacy Audit

Status: LATEST BRANCH REVISION CI-PASSED / TASK ACCEPTANCE BLOCKED — branch `task/28-security-privacy-audit`; CI run #860 passed on commit `dcea4e623065571d8669b9e098e9680077dfbb83`. The `__Secure-` cookie-name change and regression test passed. Production use with real patient data remains blocked.

- Target: OWASP ASVS v5.0.0 Level 2 (scoped engineering assessment, not certification).
- Code remediations and regression tests: login redirect validation, production-default Secure session cookies, practitioner patient-edit/mobile-reveal privilege boundary, immediate session revocation on staff deactivation, SQL-looking search input, mass-assignment defenses and Kavenegar SSRF/redirect/credential-leak controls.
- CI evidence: 280 Laravel/Pest tests passed (24,493 assertions); 36 Playwright tests passed; PostgreSQL migrations, Pint, PHPStan, JavaScript lint/typecheck/build, Composer audit, npm audit and full-history Gitleaks passed.
- Audit artifacts: [Task 28 status](task28-status.md), [ASVS checklist](security/task28-asvs-checklist.md), [full ASVS requirement register](security/task28-asvs-v5.0.0-level2-register.csv), [route authorization matrix](security/task28-route-authorization-matrix.md), [threat model](security/task28-threat-model.md), [privacy impact assessment](security/task28-privacy-impact-assessment.md), [data inventory](security/task28-data-inventory.md), [third-party flows](security/task28-third-party-flows.md), [findings register](security/task28-findings.md), [clinic-owner decisions](security/task28-owner-decisions.md).
- Release blockers: open high retention finding SEC-28-003; open high ASVS Level 2 MFA finding SEC-28-012; Smart Smile consent-token TTL; staff session timeout policy; production infrastructure evidence; full route-by-route IDOR/CSRF/DOM-XSS/distributed rate-limit verification; supplementary secret-surface review; Kavenegar URL-path key exposure decision before SMS activation.
- Do not merge Task 28 as complete or mark it verified for production until all critical/high findings are closed and required privacy/retention decisions are approved.

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
