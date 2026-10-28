# Task 16 — Connect Existing Booking UI to the Real API

**State: IMPLEMENTED IN LOCAL PATCH; VERIFICATION PENDING. Not pushed or merged.**
Task 16 is part of the frontend track after Tasks 05/06. It can proceed in parallel with Task 15 because Tasks 13 and 14 already provide availability and transactional booking creation. The repo's original Task 16 dependency label is superseded by the owner's clarified track plan.

## Repository facts checked
- Public repository main is readable. The inspected main head is merge commit `9920f2b9dc80352412921bff20c8241f61c7f49e` (PR #15, Task 14).
- `docs/STATUS.md` and `docs/task14-status.md` still claimed Task 14 merge was pending. Updated both to reflect the repository's actual main history.
- Actual Task 14 route: `POST /api/v1/appointments`, request fields `full_name`, `mobile`, `service_id`, `start_at`, required `Idempotency-Key`; success is 201 with `status`, `start_at`, `end_at`, `expires_at`; the public controller strips internal `appointment_id`.
- Actual Task 13 route: `/booking/availability/{service}`. It originally exposed practitioner/resource IDs. The new versioned adapter hides those IDs and scopes the results by date.
- No public service listing endpoint existed. Task 16 adds a minimal endpoint exposing only services currently marked active and public-bookable.
- D8 mobile OTP was explicitly confirmed as required by the owner during this task; SMS vendor/subscription, budget, consent copy and integration secret remain unresolved. A default-on server gate now fails closed until Task 15 implements an approved verification grant.

## Changes
- `resources/js/booking/api-client.js`, `retry.js`, `error-messages.js`, `format.js`, `wizard.js`: one JSDoc-typed client and a replacement booking wizard in the existing Blade/Vite runtime. Same modal IDs, classes, labels and five-step flow; no frontend framework.
- `resources/js/app.js`: imports the new wizard, removes fake booking code from the runtime and removes the stale simulated `AK_DATA.booking` network options. Other frontend modules untouched.
- `resources/css/booking-a11y.css`, `resources/css/app.css`: scoped error summary and screen-reader live announcements, no typography redesign or Persian letter-spacing changes.
- `resources/views/layouts/app.blade.php`: CSRF meta tag.
- `resources/views/home-body.blade.php`: error/live regions; current date copy; removes false prototype notice; confirmation says **booking request received**, not confirmed; shows pending clinic approval, not an internal UUID as a tracking number.
- `routes/api.php`, `PublicServicesController.php`, `PublicAvailabilityController.php`: versioned service list and date-scoped availability routes; CSRF-enabled Task 14 POST; throttle limits.
- `AvailabilityController.php`: legacy public availability route now omits practitioner/resource IDs.
- `tests/Feature/PublicBookingUiApiTest.php`: tests service visibility, date validation, availability redaction and CSRF meta.
- `tests/js/booking-api.test.mjs`: 8 Node tests cover exact Laravel paths/payload, copy safety, idempotent retries, errors, response validation and Tehran formatting.
- `tests/browser/booking-api.spec.js`: 5 Playwright cases cover server-confirmed success, slot conflict, network retry with same key and keyboard-only completion.
- `docs/task16-api-contract.md`, `docs/phone-verification-provider-seam.md`, `docs/STATUS.md`, `docs/RESUME.md`, `docs/task14-status.md`.

## Retry, privacy and security
- GET up to 3 attempts; POST up to 3 attempts only on ambiguous network/timeouts or 502/503/504; same key reused for same unchanged attempt.
- Exponential full jitter, 350 ms base, 3 s cap; timeout 10 s GET / 15 s POST.
- POST sends same-origin credentials, `X-CSRF-TOKEN` and Idempotency-Key. Laravel `web` middleware adds session + CSRF protection.
- The client discards raw server message text and maps status/error codes to fixed Persian/English text. Only allowlisted validation field names are used.
- Availability response omits provider/resource IDs. No staff/admin imports added to public entry; bundle test remains pending.
- Name/mobile appear only in the booking request/review UI; no logging or analytics added. Test data is synthetic. No external provider or real phone used.

## Acceptance status
| Criterion | State | Evidence |
|---|---|---|
| All API errors map to tested safe messages | Partially met | Frontend map + unit tests; copy is repository-owned placeholder until clinic review; PHP API response shapes are covered by the added feature tests, which were not executable here |
| Retries cannot duplicate booking | Client met, end-to-end server verification pending | Client retries keep identical key; Task 14 server idempotency is documented and already tested upstream |
| Public bundle contains no staff-only code | Pending | Existing CI build/Pest coverage not executed in sandbox |
| Real flow succeeds only after server confirmation | Client met, browser run pending | Strict 201 + status/time validation in client, without rendering internal IDs; success browser test authored |
| Keyboard access, focus/error summary, RTL announcements | Implemented, browser run pending | Wizard focus management, alert/live region, Persian date/time labels; Playwright test authored |

## Commands/evidence
- Executed: `node tools/smoke-test.mjs` → **PASS**.
- Executed: `node --test tests/js/booking-api.test.mjs` → **8 passed, 0 failed**.
- Executed: `node --check` on changed booking JS and `resources/js/app.js` → syntax checks passed.
- Not executable here: `npm` and `php`/Composer are absent; therefore `npm run lint`, `npm run typecheck`, `npm run build`, Pint, PHPStan, `php artisan test`, PostgreSQL migration tests and `npm run test:browser` were not run.
- Visual comparison at 375/768/1440 was not run. UI styles were scoped; still require project CI/browser evidence before saying VERIFIED.

## Open boundaries (not blockers for Task 16 implementation)
- Task 15 must add OTP/verification because D8 is confirmed, plus privacy consent, anti-abuse, request status and any patient-safe tracking token. Integrate only after provider/policy approval and its API contract exist.
- No patient tracking code exists in Task 14; the UI does not fabricate one or expose `appointment_id` (stripped by the public controller). Public creation is currently blocked by `BOOKING_PHONE_VERIFICATION_REQUIRED=true` until Task 15 completes.
- Service list remains empty until services are explicitly activated and approved for public booking by clinic staff.

## Rollback / next action
Revert this patch to return to the simulated wizard; no migration/schema change. Apply the patch to a branch, run the full CI sequence and browser tests with synthetic data, then review the resulting diff before merge. No production command or database operation was run.
