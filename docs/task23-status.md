# Task 23 — AI provider abstraction and cost controls

Status: IMPLEMENTED — pending CI verification.

## Scope

AI is an application boundary, not a direct SDK dependency. Text generation, image analysis, and image transformation use explicit provider contracts and a centralized manager.

## Provider policy

- Default provider is the deterministic fake provider.
- All AI capability flags default to disabled.
- No real AI vendor SDK or network call is introduced.
- Model names, timeouts, provider selection, rate limits, and pricing inputs are centralized in `config/ai.php`.
- The fake provider returns deterministic synthetic output and never receives patient records or raw image bytes.

## Privacy and data flow

The manager accepts only bounded context keys (`locale`, `content_ids`, `feature_variant`).
Prompts are passed only to the provider abstraction and are never persisted.
Image operations use an opaque image reference rather than raw image bytes.
AI request records contain feature, status, a system actor marker, and bounded approved content IDs only.
Provider failure logs contain only capability and feature metadata; prompt/image contents are deliberately excluded.

Third-party data flow is currently none because no real provider is configured or contacted. Any future provider requires owner approval of provider/privacy terms before activation.

## Failure and fallback

- Disabled capability: returns `disabled` without invoking a provider.
- Rate limit exceeded: returns `rate_limited`.
- Provider exception or unapproved provider: logs only safe operational metadata and returns the configured text fallback, or null for image operations.
- Network retry behavior is intentionally not added here; a future network provider must define its retry/timeout policy without leaking payloads.

## Cost and usage

Successful provider calls update `ai_usage` by UTC day and feature with input/output token counts, request count, and configured estimated cost.
`ai_requests` stores privacy-safe operational metadata for each provider attempt.
A configurable daily estimated-cost ceiling and per-minute request limit prevent unbounded use.

## Release gate

All AI capabilities remain disabled until the Task 27 release gates pass and owner-approved provider/privacy terms are available.

## Deliberate omissions

- No real AI provider SDK or credentials.
- No public AI endpoint.
- No Smart Smile clinical behavior.
- No patient prompt/image persistence.
- No provider activation before owner approval.
