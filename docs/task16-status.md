# Task 16 — Connect Existing Booking UI to the Real API

**State: MERGED; CI VERIFIED.**

## Branch / commit
- Task branch: `task/16-booking-ui-api-integration`
- Task implementation head: `464c9e07356fb7e5b48c5d69df296fc0784e3c78`
- Merge commit on `main`: `2ad1e590e93eaaa38965cfbfeff660c0a9f47cf2`
- Post-merge CI run: `40740015793` — **SUCCESS**
- PR: #17

## Implemented

- Existing Blade/Vite booking wizard consumes the real Laravel APIs from Tasks 13/14.
- Added versioned public service catalog: `GET /api/v1/services`.
- Added date-scoped, capacity-redacted availability: `GET /api/v1/availability/{service}?date=YYYY-MM-DD`.
- Connected booking submission to `POST /api/v1/appointments`.
- Preserved existing modal IDs/classes, five-step flow, RTL layout, Estedad typography and framework-free architecture.
- Removed fake booking success/reference behavior; success is rendered only after a valid server 201 response.
- Public appointment responses do not expose internal appointment UUIDs.
- Availability responses do not expose practitioner/resource IDs.
- Added bounded retry behavior while preserving the same Idempotency-Key for the same booking attempt.
- Raw server error text is not shown to users; fixed Persian/English error copy is used.
- Added public POST CSRF protection through existing Laravel web middleware.
- Added keyboard/focus/error-live-region accessibility support.
- Added responsive regression coverage at 375px, 768px and 1440px.

## Security / OTP boundary

- Mobile OTP is owner-confirmed as required, but Task 15 implementation is intentionally deferred.
- `BOOKING_PHONE_VERIFICATION_REQUIRED=true` remains enabled by default.
- Until Task 15 provides a valid server-side verification grant, public appointment creation fails closed with `503 PHONE_VERIFICATION_NOT_READY` and creates no appointment.
- No SMS provider, SDK, credential, real message or provider secret was added.
- Tests use synthetic data only.

## Verification evidence

The task-branch CI passed:
- JavaScript lint/typecheck/build
- PHP formatting
- PHPStan
- PostgreSQL migrations
- 169 Laravel tests / 23,613 assertions
- browser tests
- responsive regression screenshots/tests at 375/768/1440px

Post-merge CI run `40740015793` on main also completed with **SUCCESS**.

## Acceptance

| Criterion | State | Evidence |
|---|---|---|
| Public responses reveal only permitted information | Met | Redacted service/availability/appointment responses and tests |
| Internal appointment ID not exposed publicly | Met | Public response stripping and API/browser tests |
| Booking success requires real server response | Met | API client and browser success test |
| Retry preserves idempotency key | Met | Frontend/API tests |
| Raw API/server errors are not exposed | Met | Error mapping and browser tests |
| OTP production gate fails closed | Met | 503 gate test; no appointment created |
| Responsive frontend regression | Met | 375/768/1440 screenshots/tests |
| No staff-only code in public bundle | Met | Public bundle test |
| No real messaging/provider | Met | No provider integration; synthetic CI only |

## Deliberate omissions

- Task 15 OTP/VerificationChallenge implementation.
- SMS provider integration and credentials.
- Privacy notice wording/version and consent workflow.
- Patient tracking-token/status lookup.
- Real SMS delivery.
- Anti-abuse policy decisions that belong to Task 15.

## Rollback

Task 16 has no database migration. Reverting the Task 16 merge returns the public booking UI to the prior state. Do not revert Tasks 13/14 independently.

## Next step

Task 16 is merged and CI-verified. Task 15 remains intentionally deferred. Proceed to the next approved task only after checking its dependencies and owner decisions.
