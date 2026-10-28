# Task 16 — Connect Existing Booking UI to the Real API

**State: VERIFIED ON TASK BRANCH; NOT MERGED.**

## Branch / commit
- Branch: `task/16-booking-ui-api-integration`
- Verification commit: `35657b6ab3ef288c773b54e91a3204f3790fe9c4`
- CI run: `40737793534` — **SUCCESS**
- Base: `main` at Task 14 merge `9920f2b9dc80352412921bff20c8241f61c7f49e`

## Implemented
- Existing Blade/Vite booking wizard now consumes the real Laravel APIs from Tasks 13/14.
- Added versioned public service catalog: `GET /api/v1/services`.
- Added date-scoped, capacity-redacted availability: `GET /api/v1/availability/{service}?date=YYYY-MM-DD`.
- Connected booking submission to `POST /api/v1/appointments`.
- Preserved the existing modal IDs/classes, five-step flow, RTL layout, Estedad typography and existing frontend framework-free architecture.
- Removed fake booking success/reference behavior; success is rendered only after a valid server 201 response.
- Public appointment responses do not expose the internal appointment UUID.
- Availability responses do not expose practitioner/resource IDs.
- Added bounded retry behavior and preserves the same Idempotency-Key for the same booking attempt.
- Raw server error text is not shown to users; fixed Persian/English error copy is used.
- Added CSRF protection to the public POST through the existing Laravel web middleware.
- Added keyboard/focus/error-live-region accessibility support.
- Added responsive regression coverage at 375px, 768px and 1440px.

## Security / OTP boundary
- Owner-confirmed decision: public booking requires mobile OTP verification.
- Task 15 is intentionally deferred while provider, subscription, budget, privacy consent and operational verification policy are gathered.
- `BOOKING_PHONE_VERIFICATION_REQUIRED=true` remains enabled by default.
- Until Task 15 provides a valid server-side verification grant, public appointment creation returns **503 PHONE_VERIFICATION_NOT_READY** and creates no appointment.
- No SMS provider, SDK, credential, real message or provider secret was added.
- Tests use synthetic data only.

## Verification evidence
CI run `40737793534` completed successfully with:
- JavaScript lint — PASS
- JavaScript typecheck — PASS
- Vite build — PASS
- PHP formatting — PASS
- PHPStan — PASS
- PostgreSQL migrations — PASS
- Laravel tests — **169 passed, 23,613 assertions**
- Browser tests — **10 passed**
- Responsive screenshots — **375px, 768px, 1440px** uploaded as Task 16 artifact
- No production commands or real messaging were used.

The responsive browser regression explicitly verifies:
- no horizontal overflow at all three widths;
- all three hero-stat icons remain present and visible;
- Persian hero copy has normal letter-spacing;
- the hero is fully ready before screenshots are captured.

## Acceptance
| Criterion | State | Evidence |
|---|---|---|
| Public responses reveal only permitted information | **Met** | Redacted service/availability/appointment responses + feature tests |
| Tracking/reference does not expose internal appointment ID | **Met** | Public controller strips `appointment_id`; browser/API tests assert no UUID |
| Booking succeeds only after real server response | **Met** | API client validates 201 response; Playwright success test |
| Retry preserves idempotency key | **Met** | Node + Playwright tests |
| Raw API/server errors are not exposed | **Met** | Error mapping tests + browser conflict/OTP tests |
| OTP production gate fails closed | **Met** | 503 gate test; no appointment created |
| Responsive frontend regression | **Met** | CI screenshots/tests at 375/768/1440 |
| No staff-only code in public bundle | **Met** | Public bundle feature test passes |
| No real messaging/provider | **Met** | No provider integration; synthetic CI only |

## Deliberate omissions
- Task 15 OTP/VerificationChallenge implementation.
- SMS provider integration and credentials.
- Privacy notice wording/version and consent workflow.
- Patient tracking-token/status lookup.
- Real SMS delivery.

These are intentionally outside Task 16 and remain part of the deferred Task 15 track.

## Rollback
Task 16 introduces no database migration. Reverting the Task 16 branch commits returns the public booking UI to the prior simulated behavior. Do not revert Tasks 13/14.

## Next step
Task 16 is **verified on the branch but not merged into `main`**. Merge PR #17 only when the owner explicitly authorizes the merge.