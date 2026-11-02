# Project status

Last updated: 2026-11-02

## Task 28 — Security and Privacy Audit

Status: IN PROGRESS / BLOCKED — branch `task/28-security-privacy-audit`; no Task 28 CI evidence recorded yet. Production use with real patient data remains blocked.

- Target: OWASP ASVS v5.0.0 Level 2.
- Code remediations and regression tests: login redirect validation, production-default Secure session cookies, practitioner patient-edit/mobile-reveal privilege boundary, immediate session revocation on staff deactivation, SQL-looking search input and mass-assignment tests.
- CI additions: Composer audit, npm audit and full-history Gitleaks scan.
- Audit artifacts: [Task 28 status](task28-status.md), [ASVS checklist](security/task28-asvs-checklist.md), [threat model](security/task28-threat-model.md), [privacy impact assessment](security/task28-privacy-impact-assessment.md), [data inventory](security/task28-data-inventory.md), [third-party flows](security/task28-third-party-flows.md), [findings register](security/task28-findings.md).
- Release blockers: owner-approved retention for operational/audit/queue/consent/AI/log/backup records; Smart Smile consent-token TTL; session timeout policy; production infrastructure evidence; pending security/dependency scan results.
- Do not mark Task 28 verified or complete until all critical/high findings are closed and required privacy/retention decisions are approved.

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
