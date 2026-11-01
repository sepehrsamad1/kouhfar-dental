# Task 25 — Laravel AI assistant UI integration

Status: IMPLEMENTED — pending CI verification.

## Implemented

- Replaced the scripted frontend assistant replies with the bounded Task 24 Laravel assistant.
- Versioned public endpoint: `POST /api/v1/assistant`.
- Preserved the existing assistant panel, launcher, quick prompts, input and overall visual structure.
- Added explicit AI disclosure that the assistant is informational and not a clinician or replacement for a dentist.
- Rendered assistant output with `textContent`; generated HTML is never injected into the chat.
- Source references are rendered from server-provided approved content metadata only.
- Source links are accepted only when same-origin and under `/content/`.
- Added visible states for disabled, unavailable, refusal, emergency, provider failure/fallback and rate limiting.
- Raw non-2xx server responses are not shown to users.
- Added browser coverage for grounded answers, refusal, emergency, provider failure, hostile model output/XSS, and rate limiting.
- Added endpoint coverage for validation, disabled state, source links and public throttling.
- CI browser tests use the deterministic fake AI provider only; no real AI provider or credentials are enabled.

## Privacy and security

- No patient/staff data was added to the assistant UI or API context.
- Model output remains untrusted.
- No generated links are accepted from model text.
- The existing Task 24 knowledge boundary and appointment/patient restrictions remain authoritative.

## Deliberate omissions

- No external AI provider.
- No clinician impersonation.
- No HTML/Markdown rendering of model output.
- No patient records, staff notes or appointment mutations.
