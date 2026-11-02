# Task 27 — AI evaluation, safety gates, and cost controls

Status: IMPLEMENTATION IN PROGRESS — not verified.

## Owner-approved values

See task27-owner-decisions.md. Dataset v1.0.0 contains 100 Persian and 100 English cases. Thresholds: 95% citation presence, 95% grounding, and 100% for refusal correctness, emergency response, and forbidden-content prevention. Monthly budget ceiling: 10,000,000 IRR.

## Existing implementation inspected

- Task 23 provider abstraction, usage aggregation, rate limits, fake provider, and disabled-by-default flags.
- Task 24 published-content-only assistant, emergency bypass, refusal behavior, and source references.
- Task 25 versioned assistant API and safe frontend rendering.
- Task 26 Smart Smile remains behind its release gate.

## Evaluation dataset

The versioned dataset is tests/Fixtures/AI/task27-evaluation-v1.json and contains 200 cases across normal dental questions, specialist treatment questions, off-domain requests, diagnosis/prescription requests, emergency red flags, prompt injection, secret exfiltration, harmful requests, ambiguous requests, and citation-required questions.

## Research sources for FAQ curation

- ADA MouthHealthy: https://www.mouthhealthy.org/all-topics-a-z
- ADA Oral Health Topics: https://www.ada.org/resources/ada-library/oral-health-topics
- NHS dental treatments: https://www.nhs.uk/live-well/healthy-teeth-and-gums/dental-treatments/
- NHS root canal treatment: https://www.nhs.uk/tests-and-treatments/root-canal-treatment/
- NHS emergency dental care: https://www.nhs.uk/nhs-services/dentists/how-to-find-an-nhs-dentist-in-an-emergency/
- NHS toothache and abscess: https://www.nhs.uk/symptoms/toothache/ and https://www.nhs.uk/conditions/dental-abscess/
- CDC adult oral health: https://www.cdc.gov/oral-health/prevention/oral-health-tips-for-adults.html
- CDC dental infection prevention: https://www.cdc.gov/dental-infection-control/hcp/summary/standard-precautions.html
- ADA whitening review: https://www.ada.org/resources/ada-library/oral-health-topics/whitening/
- NIDCR patient information on tooth decay, dry mouth, and gum disease: https://www.nidcr.nih.gov/health-info
- AAOMS wisdom-tooth management: https://aaoms.org/wp-content/uploads/2024/05/wisdom_teeth_management.pdf
- Iranian Pediatric Dentistry Association patient FAQ: https://www.kapd.ir/faq/
- Shahid Beheshti Dental Hospital patient education: https://app.shdh.ir/پیشگیری-و-بهداشت/
- Tehran University of Medical Sciences Faculty of Dentistry: https://dentistry.tums.ac.ir/

## Task 27 implementation notes\n\n- The 100 Persian and 100 English FAQ questions and answers are distinct, source-linked drafts; the seeder rejects duplicate questions/answers or missing source references. They remain unpublished pending clinician review.\n- The initial monthly budget is 10,000,000 IRR and is editable by authorized administrators; changes are validated and audit-logged.\n- Evaluation reports record provider version, model version, prompt version, and dataset version and are uploaded as a CI artifact.
- Exact published FAQ matches are served without a model request; budget exhaustion can therefore fall back to approved FAQ content, while feature/emergency disable still overrides it.
- The assistant blocks obvious identifying information before provider prompts, filters retrieved knowledge with common injection/secret patterns, and rate-limits by IP plus user/session.
- Admin usage includes daily feature-level totals and explicit fake-provider/unknown-quota status; no real provider telemetry is fabricated.\n\n## Limitations before VERIFIED

- CI must execute the evaluation runner and prove the approved thresholds.
- Provider-specific live billing and quota status cannot be asserted while only the fake provider is configured; unknown must remain explicit.
- FAQ material must remain reviewable and must not be silently auto-published as clinic-approved clinical advice.
- Admin budget controls, model-level usage visibility, audit logging, emergency disable, and safe fallback must be implemented and tested.
- No real provider, API key, patient data, or production system is activated by this task.


## Task 27

- Status: IN PROGRESS — implementation and CI verification pending.
- Owner-approved evaluation size: 100 Persian + 100 English; monthly budget: 10,000,000 IRR.
- Branch: task/27-ai-evaluation-cost-safety.
- See docs/task27-status.md and docs/task27-owner-decisions.md for implementation details and release limitations.
