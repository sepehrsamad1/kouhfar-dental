# Task 13 — Availability engine and timezone-correct slot generation

Status: IN PROGRESS — pure core and initial unit tests committed; database adapter, API integration, property tests and benchmark remain.

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

## Implementation record
- Added `App\Domain\Availability\AvailabilityEngine` as a database-independent initial slot calculator.
- Added initial deterministic unit tests for grid alignment, buffered fit, configured closures, and expired versus active pending holds.
- The current engine is a core starting point, not yet the production availability feature. It still needs careful review against persisted schedule semantics and overlap ranges before being exposed to clients.

## Remaining work
1. Inspect and implement a database adapter for clinic/practitioner hours, breaks/exceptions, service eligibility, compatible active resources, appointment pivots and occupied intervals.
2. Resolve whether clinic-wide and practitioner-specific hours/exceptions are combined by intersection/override, using the actual Task 12 configuration model; do not silently invent semantics.
3. Add an availability endpoint matching the existing API/architecture contract, validate inputs, authorize appropriately, and set `Cache-Control: no-store`.
4. Ensure a 1-hour pending expiry is assigned when pending holds are created, and expired pending appointments are excluded based on server clock even before any scheduled expiry job runs.
5. Add property/invariant tests for hours, notice, horizon, occupancy, practitioner/resource conflicts and timezone behavior; add tricky-date fixtures.
6. Benchmark the representative workload and record actual timing.
7. Run Pint, PHPStan, PHPUnit/Pest and CI; fix any issues before reporting verification.

## Algorithm contract
- Core calculation remains database-independent; adapter owns all reads.
- A slot must fit the complete service plus pre/post buffers inside valid working time and have a conflict-free practitioner and compatible shared resource.
- Interpret schedules in `Asia/Tehran`; return UTC ISO instants and a separate local display value.
- Confirmed appointments occupy capacity; pending holds occupy capacity only while their expiry is after the server clock. Cancelled, expired, completed and no-show appointments do not block future slots.
- Availability reads alone do not make booking writes race-safe. Booking creation must keep database-backed transactional/exclusion protection.
- Public holidays are ordinary working dates unless an explicit configured exception says otherwise.

## Verification
- No test run or benchmark result is claimed yet. No production migrations, seeding, deployment or merge to `main` has been performed.
