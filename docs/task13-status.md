# Task 13 — Availability engine and timezone-correct slot generation

Status: IMPLEMENTED ON DRAFT BRANCH — core availability logic, database adapter, no-store endpoint, exception/break handling, randomized invariant coverage, timezone/midnight coverage, and representative performance measurements are implemented. Booking-write concurrency remains intentionally outside this task and must be enforced by the eventual booking-creation workflow.

## Branch and scope
- Branch: `task/13-availability-engine`
- Keep this PR Draft until the owner approves the completed Task 13 review; do not merge before that approval.
- Clinic timezone: `Asia/Tehran`; weekly hours Saturday–Thursday, 12:00–20:00.
- Shared resources: two dental units and one consultation space.
- Cross-midnight working periods are prohibited.

## Owner decisions (2026-10-27)
- Holiday policy: only configured exceptions entered by clinic management close/alter the schedule; Iranian public holidays are not automatically closed.
- Pending appointment hold: 1 hour. A pending appointment occupies capacity only while `expires_at` is strictly later than the injected/server clock.
- Minimum booking notice: 24 hours from the current instant.
- Booking horizon: through the end of the local date 14 days after the current local date; later slots are excluded.
- Slot-start granularity: 5 minutes, aligned to local wall-clock minutes.
- Proposed pilot performance target: p95 <= 250 ms and p99 <= 500 ms for a 14-day query with up to 10 active practitioners, 3 shared resources, 30 configured services, and 1,000 relevant appointment records, measured on the CI runner with a warmed process and no external network calls. Record sample count and environment. This is an engineering target, not a production SLA.

## Files added
- `app/Domain/Availability/AvailabilityEngine.php`: database-independent slot calculator with per-capacity occupancy indexes, merged intervals, and binary-search conflict checks.
- `app/Domain/Availability/AvailabilityRepository.php`: adapter reading service eligibility, clinic/practitioner hours, breaks, configured exceptions, practitioners, compatible resources and appointment occupancy. The appointment lookup widens its range by maximum configured service buffers and filters to appointments touching eligible practitioner/resource capacity.
- `app/Http/Controllers/AvailabilityController.php`: public JSON endpoint with `Cache-Control: no-store, private`.
- `routes/web.php`: `GET /booking/availability/{service}` named `booking.availability`.
- `tests/Unit/AvailabilityEngineTest.php`: unit tests for five-minute grid, buffered fit, configured closures, pending expiry, minimum notice, horizon, capacity conflicts, plus an engine-core benchmark.
- `tests/Feature/AvailabilityEndpointTest.php`: no-store response, inactive/unapproved service behavior, special-opening support, and closure precedence.

## Verification and deliberate scope boundaries
- Latest CI run for the branch (`40512353832`, commit `d791e2dd426f0a1d9e560d9eb5373a350297352c`) passed: JavaScript lint/typecheck/build, Pint, PHPStan, PostgreSQL migrations, Laravel tests, and browser smoke tests. The PR currently reports 147 Laravel tests and 1,486 assertions before these additional regression/property cases.
- A follow-up migration makes `schedule_exceptions.practitioner_id` nullable so clinic-wide exceptions accepted by the configuration controller can be persisted; the PostgreSQL migration test passed in CI.
- The adapter now adds manager-configured `special_opening` intervals (clinic-wide or practitioner-scoped), merges overlaps, subtracts breaks, and applies `closed`/`holiday` intervals last so a closure cannot be re-opened. The PostgreSQL feature test for special-opening support and closure precedence passed in CI. Public holidays are not inferred automatically.
- Pending holds must be assigned `expires_at = server_now + 1 hour` by the eventual request-creation workflow. That workflow is not part of this endpoint.
- Availability reads do not themselves make booking creation race-safe. The eventual write path must use transactions and database-backed conflict protection. Existing database exclusion ranges currently represent appointment intervals; verify how service buffers should be protected at booking-write time before claiming race-safe buffer enforcement.
- Unit coverage includes buffered working-period fit, five-minute alignment, pending expiry, exact minimum-notice boundary, booking-horizon exclusion, and independent practitioner/resource conflict checks. Adapter-level tests cover configured special openings and closure precedence; both passed in the successful CI run.
- Engine-core benchmark: 10 samples, 1,000 appointments, 10 practitioners, 3 resources, 14 days; latest recorded p95/p99 was 187.25 ms on the CI runner. A database-backed benchmark is also present for 1,000 relevant appointments, 10 practitioners, 3 resources, 30 services, and 14 days; its measurements are emitted by CI rather than used as a flaky hard assertion.
- The public endpoint returns no patient data, uses `Cache-Control: no-store, private`, and is rate-limited to 60 requests/minute. Practitioner/resource identifiers are returned because the availability result is capacity-specific; the public booking UI is not connected by this task.
- No production migrations, seeding, deployment or merge to `main` has been performed.

## Remaining work outside Task 13
- Pending creation must assign `expires_at = server_now + 1 hour`; this task only makes the pure availability engine ignore expired pending holds using the supplied/server clock.
- Booking creation must use a transaction plus the existing PostgreSQL conflict protection. Buffer enforcement at write time must be implemented and verified by the booking-creation task; availability reads cannot make writes race-safe.

## Algorithm contract
- Core calculation remains database-independent; adapter owns all reads.
- A slot must fit the complete service plus pre/post buffers inside valid working time and have a conflict-free practitioner and compatible shared resource.
- Interpret schedules in `Asia/Tehran`; return UTC ISO instants and a separate local display value.
- Confirmed appointments occupy capacity; pending holds occupy capacity only while their expiry is after the server clock. Cancelled, expired, completed and no-show appointments do not block future slots.
- Public holidays are ordinary working dates unless an explicit configured exception says otherwise.
