# Task 24 — Grounded public dental assistant

Status: IMPLEMENTED — pending CI verification.

## Standards basis

The initial approved knowledge package combines:
- Iran's Patient Rights Charter principles: appropriate and respectful care, sufficient information, informed participation in decisions within applicable rules, privacy/confidentiality, and access to an effective complaints process.
- CDC dental infection-prevention principles: Standard Precautions, hand hygiene, PPE, sharps safety, safe injection practices, instrument reprocessing/sterilization, and environmental disinfection.
- International dental emergency triage guidance: uncontrolled bleeding, severe/rapid swelling with airway risk, severe breathing/swallowing difficulty, loss of consciousness, and serious facial trauma are red flags for emergency assessment.
- Iran emergency medical services number: 115.

The knowledge deliberately avoids Kouhfar-specific prices, credentials, hours, guarantees, outcomes, provider claims, or business policies that were not supplied.

## Implemented

- PublishedContentProvider is the only public AI knowledge source.
- Public assistant endpoint: POST /api/v1/assistant.
- Persian and English responses.
- Source references use approved content_key and version.
- Prompt-injection/internal-data requests are refused before model invocation.
- Diagnosis, prescribing, dosage, individualized treatment decisions, unsupported pricing, and competitor comparisons are refused.
- Emergency red flags bypass the model and return the approved emergency response.
- Patient records and appointment mutations are inaccessible to the assistant.
- AI request records contain only bounded approved content references; raw prompts are not persisted.
- Rate limiting and Task 23 cost controls remain active.
- Draft/review/archived content cannot enter assistant context.
- Safe unavailable/failure behavior is returned when knowledge or provider output is unavailable.

## Release gate

No external AI provider or credentials were added. Task 23's fake provider remains the default and AI remains disabled by default until the Task 27 release gate and provider/privacy approval.

## Owner confirmation

The project owner explicitly confirmed the standards-based knowledge and emergency response researched for Task 24 on 2026-11-01.
