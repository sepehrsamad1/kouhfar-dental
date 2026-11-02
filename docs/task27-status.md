# Task 27 — AI evaluation, safety gates, dental knowledge and cost controls

Status: IMPLEMENTATION IN PROGRESS — final CI and release-gate evidence pending.

## Owner-approved decisions

See task27-owner-decisions.md. Approved: 100 Persian + 100 English evaluation cases; citation presence >=95%; grounding >=95%; refusal correctness 100%; emergency response 100%; forbidden-content prevention 100%; monthly budget 10,000,000 IRR; financial alerts 50/80/100%; provider quota alerts 80/95% when provider quota telemetry exists; exact standard fallback message; Dr. Kouhfar assistant identity; dental-only public domain.

## Implemented in this branch

- Versioned v1.0.0 bilingual evaluation dataset with normal and specialist dental questions, off-domain cases, diagnosis/prescription refusals, emergencies, prompt injection, secret exfiltration, harmful requests, ambiguity, identity override, adversarial retrieved-content instructions, and citation-required cases.
- Deterministic Persian/English guard with text normalization, domain restriction, emergency routing, medical refusal, fixed identity, and prompt-injection/secret-exfiltration checks.
- Fixed assistant persona prompt, standard domain refusal and owner-approved outage/budget fallback text.
- 100 distinct bilingual FAQ questions and answers in resources/knowledge/dental-faq-v1.json, with 22 source references from ADA, NHS, CDC, NIDCR, AAOMS and Iranian dental/academic sources; confidence labels, emergency warnings and explicit draft-for-clinician-review status.
- DentalFaqDraftSeeder imports 200 locale records into the existing content workflow as unpublished clinical drafts. DentalFaqTestingSeeder can publish these only in the testing environment for deterministic evaluation.
- AIReleaseGate calculates citation, lexical source-grounding, refusal, emergency and forbidden-output metrics; known-bad-answer regression test verifies the gate fails.
- AssistantEvaluationRunner and php artisan ai:evaluate generate a versioned JSON release report with dataset/prompt/provider/model and explicit provider/model version metadata; CI uploads the report artifact.
- IRR budget settings table, per-provider/model/capability token and cost aggregates, per-request success/failure metadata, model-specific IRR pricing configuration, monthly 10,000,000 IRR ceiling, 50/80/100 financial alerts, and request blocking at the ceiling.
- Admin-only AI usage and budget page, administrator-editable monthly budget initialized at 10,000,000 IRR, with authorization and audit logging, emergency disable, model usage summary, timestamped requests, audit logging, and staff-layout budget alerts.
- Exact matches to published, clinically approved FAQ entries are answered directly from the knowledge base without a model/API call; this provides a safe FAQ fallback at budget exhaustion. Feature/emergency disable flags still override it.
- Obvious identifiers (email, Iranian mobile and common +1 phone formats, national-ID patterns, explicit patient names/records) are blocked before provider prompts; retrieved knowledge containing common prompt-injection or secret-exfiltration instructions is excluded.
- Assistant request throttling is applied by IP at the public route and by authenticated-user/public-session identity in the provider manager.
- Admin usage view includes monthly per-model totals, daily per-feature usage, request statuses/timestamps, configured provider/model, and explicit unknown quota/connection status when no real provider telemetry exists.
- Standard fallback is returned without an additional model request when AI is disabled, rate-limited, over budget, or provider processing fails.

## Important accounting and safety limits

- Actual provider billing is nullable and is never fabricated. The fake provider exposes no authenticated provider billing or quota API, so provider credit/token quota is explicitly reported as unknown. 80%/95% provider-quota alerts cannot be activated until an approved provider supplies telemetry.
- The fake provider returns a source excerpt for assistant evaluation. This makes the deterministic run reproducible but does not prove a future live model's clinical accuracy. Grounding is a lexical source-overlap proxy, not an independent clinical review.
- The FAQ set is draft clinical content and is not published to public users by this task. It requires clinician review and the existing content approval workflow before production publication.
- No real AI provider, API key, patient data, patient image, external billing API, or production service is activated.
- The assistant uses the existing published-content retrieval/ranking layer; this is bounded retrieval, not a vector-embedding database.

## Verification still required

- CI must pass Pint, PHPStan, migrations, all Pest tests, browser tests, and the new ai:evaluate command.
- The evaluation runner must meet all owner-approved thresholds on the synthetic testing knowledge base.
- A separate approved live-provider evaluation, clinician review, provider quota/billing adapter and production release approval are required before any real model is enabled.
