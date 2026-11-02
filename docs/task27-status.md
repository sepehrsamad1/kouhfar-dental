# Task 27 — AI evaluation, safety gates, dental knowledge and cost controls

Status: CI-VERIFIED — production activation remains gated.

## Owner-approved decisions

See task27-owner-decisions.md. Approved: 100 Persian + 100 English evaluation cases; citation presence >=95%; grounding >=95%; refusal correctness 100%; emergency response 100%; forbidden-content prevention 100%; monthly budget 10,000,000 IRR; financial alerts 50/80/100%; provider quota alerts 80/95% when provider quota telemetry exists; exact standard fallback message; Dr. Kouhfar assistant identity; dental-only public domain.

## Implemented in this branch

- Versioned v1.0.0 bilingual evaluation dataset with normal and specialist dental questions, off-domain cases, diagnosis/prescription refusals, emergencies, prompt injection, secret exfiltration, harmful requests, ambiguity, identity override, adversarial retrieved-content instructions, and citation-required cases.
- Deterministic Persian/English guard with text normalization, domain restriction, emergency routing, medical refusal, fixed identity, and prompt-injection/secret-exfiltration checks.
- Fixed assistant persona prompt, standard domain refusal and owner-approved outage/budget fallback text.
- 100 distinct bilingual FAQ questions and answers in resources/knowledge/dental-faq-v1.json, with 23 source references from ADA, NHS, CDC, NIDCR, AAOMS and Iranian dental-hospital/pediatric-dentistry sources; confidence labels, emergency warnings and explicit draft-for-clinician-review status.
- DentalFaqDraftSeeder imports 200 locale records into the existing content workflow as unpublished clinical drafts. DentalFaqTestingSeeder can publish these only in the testing environment for deterministic evaluation.
- Published FAQ answers carry their external evidence references through the assistant API; the browser renders only HTTPS links from the reviewed ADA, NHS, CDC, NIDCR, AAOMS, and Iranian-source host allowlist.
- AIReleaseGate calculates citation, lexical source-grounding, refusal, emergency and forbidden-output metrics; known-bad-answer regression test verifies the gate fails.
- AssistantEvaluationRunner and php artisan ai:evaluate generate a versioned JSON release report with dataset/prompt/provider/model and explicit provider/model version metadata; CI uploads the report artifact.
- IRR budget settings table, per-provider/model/capability token and cost aggregates, per-request success/failure metadata, model-specific IRR pricing configuration, monthly 10,000,000 IRR ceiling, 50/80/100 financial alerts, and request blocking at the ceiling.
- Admin-only AI usage and budget page, administrator-editable monthly budget initialized at 10,000,000 IRR, with authorization and audit logging, emergency disable, model usage summary, timestamped requests, audit logging, and staff-layout budget alerts.
- Exact matches to published, clinically approved FAQ entries are answered directly from the knowledge base without a model/API call; this provides a safe FAQ fallback at budget exhaustion. Feature/emergency disable flags still override it.
- Obvious identifiers (email, Iranian mobile and common +1 phone formats, national-ID patterns, explicit patient names/records) are blocked before provider prompts; retrieved knowledge containing common prompt-injection or secret-exfiltration instructions is excluded.
- Assistant request throttling is applied by IP at the public route and by authenticated-user/public-session identity in the provider manager.
- Admin usage view includes monthly per-model totals, daily per-feature usage, request statuses/timestamps, configured provider/model, and explicit unknown quota/connection status when no real provider telemetry exists.
- Authorized administrators can configure independent monthly internal token caps (80%/95% alerts) and IRR budgets (50%/80%/100% alerts) per configured model; hitting a model cap blocks only that model, while the global budget blocks all model calls.
- Standard fallback is returned without an additional model request when AI is disabled, rate-limited, over budget, or provider processing fails.

## Important accounting and safety limits

- Actual provider billing is nullable and is never fabricated. The fake provider exposes no authenticated provider billing or quota API, so provider credit/token quota is explicitly reported as unknown. 80%/95% provider-quota alerts cannot be activated until an approved provider supplies telemetry.
- Budget enforcement uses per-request actual billing when the provider reports it; otherwise it uses the configured per-model/capability estimate. Daily aggregates remain available for operational reporting.
- The fake provider returns a source excerpt for assistant evaluation. This makes the deterministic run reproducible but does not prove a future live model's clinical accuracy. Grounding is a lexical source-overlap proxy, not an independent clinical review.
- The FAQ set is draft clinical content and is not published to public users by this task. It requires clinician review and the existing content approval workflow before production publication.
- Emergency responses do not hard-code a phone number: local availability can change, and recent reporting described a temporary 115 interruption in Tehran. Users are directed to local emergency medical services or the nearest emergency department.
- No real AI provider or actual API key is configured; no patient data, patient image, external billing API, or production service is activated.
- Provider/model identifiers and API-key values are backend environment-configurable; `.env.example` contains blank API-key placeholders only. No credential is committed or exposed to the browser.
- Non-fake provider calls fail closed unless model-specific or capability-level IRR pricing is configured; per-model pricing can be supplied through the backend `AI_MODEL_PRICING_IRR` environment setting.
- The assistant uses the existing published-content retrieval/ranking layer; this is bounded retrieval, not a vector-embedding database.



## CI verification — 2026-11-02

<badge>CI PASS</badge> [GitHub Actions run #778](https://github.com/sepehrsamad1/kouhfar-dental/actions/runs/41436715873) verified code commit `02737dd47274f990dd7e297979fb46c00c9f3973`.

### Automated evidence

- Laravel/Pest: **262 passed**, **24,432 assertions**.
- Versioned evaluation dataset: **200 cases** (100 Persian, 100 English).
- Evaluation result: **passed**; no critical, blocking, case, or threshold failures.
- Citation presence: **100%** (release threshold 95%).
- Grounding: **100%** (release threshold 95%).
- Refusal correctness: **100%**.
- Emergency response: **100%**.
- Forbidden-content prevention: **100%**.
- Playwright: **36 passed**.
- Responsive AI-admin checks: **375px, 768px, 1440px**; screenshot artifacts uploaded.
- JavaScript lint, type-check, production build, Pint, PHPStan, PostgreSQL migrations and browser smoke tests passed.

### Exact CI commands

```bash
npm run lint
npm run typecheck
npm run build
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan migrate --force
php artisan test
php artisan db:seed --class=DentalFaqTestingSeeder
php artisan ai:evaluate
php artisan db:seed --class=BrowserTestSeeder
npx playwright install --with-deps chromium
npm run test:browser
```

### Reproducible evaluation metadata

- Evaluation run ID: `56f58090-a8c4-45f1-90a2-024626c51d23`
- Dataset: `1.0.0`
- Prompt: `task27-persona-v1`
- Provider/model: `fake` / `fake-v1`
- Provider/model version: `fake-contract-v1` / `fake-v1`
- Environment: `testing`
- Monthly budget configured: `10,000,000 IRR`
- Test-run spend: `0 IRR`
- Provider credentials: not configured
- Provider quota status: `unknown`

The deterministic report proves that the policy and release-gate implementation passes the defined synthetic scenarios. It does **not** establish the accuracy or safety of a future live provider. The report artifact is attached to CI run #778 as `task27-ai-evaluation-report`; responsive screenshots are attached as separate artifacts.

## Production release gates still open

- The 100 bilingual FAQ entries are drafts. A qualified clinic reviewer must review and approve clinical content through the existing content workflow before public publication.
- The clinic must separately approve a real AI provider/model and configure its server-side credentials and model-specific IRR pricing. No provider has been connected by this task.
- Provider credit/token quota remains explicitly unknown until an approved provider exposes authenticated quota/billing telemetry. The 80%/95% provider-quota alerts cannot report real values before that integration.
- A provider-backed evaluation must be run against the approved model before enabling the public AI feature. The fake-provider evaluation is not a substitute.
- Production activation remains behind the existing AI feature flags and requires clinic release approval.

No production command, destructive database operation, real patient data, real API key, or external provider connection was used.
