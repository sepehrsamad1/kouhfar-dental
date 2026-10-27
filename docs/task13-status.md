# Task 13 — Availability engine and timezone-correct slot generation

Status: IN PROGRESS — owner decisions recorded; implementation on this branch only.

## Branch and scope
- Branch: `task/13-availability-engine`
- Do not merge to `main` without explicit owner approval.
- Clinic timezone: `Asia/Tehran`.
- Recurring clinic hours: 12:00–20:00, Saturday through Thursday.
- Shared resources: two dental units and one consultation space.
- Cross-midnight working periods are prohibited.

## Owner decisions (2026-10-27)
- Holiday policy: only configured exceptions entered by clinic management close/alter the schedule; Iranian public holidays are not automatically closed.
- Pending appointment hold: 1 hour. A pending appointment occupies capacity only while its `expires_at` is strictly later than the injected/server clock.
- Minimum booking notice: 24 hours from the current instant.
- Booking horizon: 14 calendar days in the clinic timezone. The final instant is the end of the local date 14 days after the current local date; slots after that boundary are excluded.
- Slot-start granularity: 5 minutes, aligned to local wall-clock minutes.
- Performance budget (proposed pilot target): availability computation should have p95 <= 250 ms and p99 <= 500 ms for a 14-day query, up to 10 active practitioners, 3 shared resources, 30 configured services, and 1,000 overlapping/relevant appointment records, measured on the CI runner with a warmed process and without external network calls. Record actual sample count and environment. This is a proposed engineering target for the pilot, not a production SLA; report if the available test environment cannot reliably measure it.

## Algorithm contract
1. Core slot calculation must be a pure, database-independent function over normalized working periods, exceptions, service duration/buffers, practitioner/service eligibility, compatible resources, occupied appointments, current instant, notice, horizon and grid.
2. Database access is isolated in an adapter. Availability reads do not themselves make booking creation race-safe; the eventual write path must use transactional/database conflict protection.
3. A slot is returned only when the complete occupied interval (start minus pre-buffer through end plus post-buffer) fits within working time and has no practitioner or compatible-resource conflict.
4. Interpret schedules in `Asia/Tehran`; return UTC ISO instants and a separate clinic-local display value.
5. Expired pending holds are ignored using the server clock, even if a scheduled expiry job has not run. Confirmed appointments always occupy capacity; cancelled, expired, completed and no-show appointments do not block future slots.
6. Availability responses use `Cache-Control: no-store` where exposed to clients.
7. Public holidays are ordinary working dates unless an explicit configured exception says otherwise.
8. Grid is fixed at five minutes for this version; service duration and buffers need not be multiples of five, but starts remain aligned to the five-minute grid.

## Verification plan
- Deterministic table tests for opening/closing boundaries, breaks, closures, minimum notice, 14-day horizon, resource and practitioner conflicts, and expired pending holds.
- Property/invariant tests: every emitted slot fits working hours and the horizon, meets notice, avoids occupancy, and has aligned starts.
- Timezone fixtures around local midnight and timezone offset changes; no cross-midnight work periods.
- Benchmark the representative workload above and record observed timings.
- No production migrations, production seeding, deployment or merge to `main` in this task.
