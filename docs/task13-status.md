# Task 13 — Availability engine and timezone-correct slot generation

Status: STARTED — design/repository inspection only. Core behavior is paused pending owner decisions listed below, as required by the task prompt.

## Branch and dependencies

- Branch: `task/13-availability-engine`
- Base: current `main`, including Task 12 merge and documentation status updates.
- Dependencies: Task 03 API/architecture contract and Task 12 configuration foundations are present.
- Confirmed clinic timezone: `Asia/Tehran`.
- Confirmed recurring clinic hours: 12:00-20:00, Saturday through Thursday.
- Confirmed resources: two shared dental units and one consultation space.
- Cross-midnight working periods are prohibited.

## Planned implementation

1. Define a pure availability domain service that receives normalized working periods, exceptions, practitioner/service eligibility, occupancy, resource capacity, current instant, minimum notice, booking horizon, and slot granularity.
2. Put Eloquent/database reads behind a dedicated adapter; keep core slot math independent of Laravel/database.
3. Treat pending appointments as capacity-occupying only while their `expires_at` is after the injected server clock; confirmed appointments always occupy capacity.
4. Require the complete service duration plus configured buffers to fit inside valid working time and require both practitioner and a compatible shared resource to be conflict-free.
5. Calculate schedule boundaries in `Asia/Tehran`; return UTC ISO instants and a separate local display value.
6. Add explicit no-store headers to availability responses where applicable.
7. Add deterministic table tests and property-based/invariant tests for working-hour boundaries, breaks, closures, occupancy, notice, horizon, resources, and timezone transitions.
8. Measure a representative clinic workload and record environment, fixture size, and observed timings.

## Owner decisions required before finalizing behavior

- **Holiday/exception policy:** Clinic timezone and weekly hours are confirmed, but exact holiday dates and whether the clinic closes on public holidays are not. No public holiday should be auto-closed without approval. Confirm whether Task 13 should honor configured `schedule_exceptions` and treat all other dates as ordinary according to weekly hours.
- **Pending hold expiry:** Product spec D6 lists 2 hours as a proposal, not an approved value. Confirm the intended expiry duration. Regardless of duration, expired pending appointments must not occupy capacity based on the server clock even if a scheduled expiry job has not yet run.
- **Minimum notice and booking horizon:** No approved values are recorded. Confirm the minimum lead time and maximum days ahead a patient may book.
- **Performance budget:** Confirm a target (for example, maximum response time and a representative date range/concurrency). Do not treat an illustrative target as approved.

## Slot granularity

Not yet finalized. Proposed implementation should use a configurable, documented fixed grid (likely 5-minute increments) and only return starts aligned to that grid; confirm before making it an API guarantee.

## Safety and scope

- No real patient data, clinic-specific practitioner identities, qualifications, break policies, or holiday closures are invented.
- No production migrations, production seeding, deployment, or merge to `main` are performed by this task branch.
- Task 13 must not claim that availability reads alone make booking creation race-safe; final booking writes must continue to use database-backed conflict protection/transactions.

## Verification record

No Task 13 code or tests have been committed yet. Verification will be recorded only after implementation and CI evidence are available.
