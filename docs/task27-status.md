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

## Limitations before VERIFIED

- CI must execute the evaluation runner and prove the approved thresholds.
- Provider-specific live billing and quota status cannot be asserted while only the fake provider is configured; unknown must remain explicit.
- FAQ material must remain reviewable and must not be silently auto-published as clinic-approved clinical advice.
- Admin budget controls, model-level usage visibility, audit logging, emergency disable, and safe fallback must be implemented and tested.
- No real provider, API key, patient data, or production system is activated by this task.
