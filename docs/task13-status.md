# Task 13 — Availability engine and timezone-correct slot generation

Status: IN PROGRESS — initial pure core, database adapter, route/controller and baseline tests committed; full verification and correctness review remain.

## Branch and scope
- Branch: `task/13-availability-engine`
- Do not merge to `main` without explicit owner approval.
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
- `app/Domain/Availability/AvailabilityEngine.php`: database-independent initial slot calculator.
- `app/Domain/Availability/AvailabilityRepository.php`: initial adapter reading service eligibility, clinic/practitioner hours, breaks, configured closures, practitioners, compatible resources and appointment occupancy. The appointment lookup now widens its range by the maximum configured service buffers so buffered conflicts just outside the requested window are not missed.
- `app/Http/Controllers/AvailabilityController.php`: public JSON endpoint with `Cache-Control: no-store, private`.
- `routes/web.php`: `GET /booking/availability/{service}` named `booking.availability`.
- `tests/Unit/AvailabilityEngineTest.php`: baseline tests for five-minute grid, buffered fit, configured closures, and pending expiry.
- `tests/Feature/AvailabilityEndpointTest.php`: no-store response and inactive/unapproved service behavior.

## Important limitations still being addressed
- This is not yet approved as production-ready. Run formatter, static analysis and tests; fix all failures.
- Review date/time semantics and configured exception behavior, especially special-opening exceptions. Public holidays are not inferred automatically.
- Pending holds must be assigned `expires_at = server_now + 1 hour` by the eventual request-creation workflow. That workflow is not part of this endpoint.
- Availability reads do not themselves make booking creation race-safe. The eventual write path must use transactions and database-backed conflict protection. Existing database exclusion ranges currently represent appointment intervals; verify how service buffers should be protected at booking-write time before claiming race-safe buffer enforcement.
- Baseline invariant coverage includes buffered working-period fit, five-minute alignment, pending expiry, exact minimum-notice boundary, and booking-horizon exclusion. Still add adapter-level fixtures for schedule exceptions, timezone boundaries, and practitioner/resource conflict cases.
- Benchmark the representative workload above and record actual timing.
- Confirm endpoint authorization/rate limiting policy before connecting it to public UI; it currently returns opaque practitioner/resource identifiers as part of each slot.
- No production migrations, seeding, deployment or merge to `main` has been performed.

## Algorithm contract
- Core calculation remains database-independent; adapter owns all reads.
- A slot must fit the complete service plus pre/post buffers inside valid working time and have a conflict-free practitioner and compatible shared resource.
- Interpret schedules in `Asia/Tehran`; return UTC ISO instants and a separate local display value.
- Confirmed appointments occupy capacity; pending holds occupy capacity only while their expiry is after the server clock. Cancelled, expired, completed and no-show appointments do not block future slots.
- Public holidays are ordinary working dates unless an explicit configured exception says otherwise.
