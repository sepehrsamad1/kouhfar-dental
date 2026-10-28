# Task 14 — Transactional appointment creation

Status: MERGED TO MAIN — CI VERIFIED. Reconciled from repository main after merge PR #15 (current inspected main head: `9920f2b9dc80352412921bff20c8241f61c7f49e`).

## Branch and scope
- Branch: `task/14-appointment-creation`
- PR: #15 (merged to `main`)
- Recorded CI run `40540118213` passed the full verification suite.
- Public booking endpoint: `POST /api/v1/appointments`.
- PostgreSQL is authoritative for capacity reservation and appointment state invariants.

## Booking contract
- Request requires full name, Iranian mobile number, service, start instant and an `Idempotency-Key` header. Practitioner and resource are selected server-side from Task 13 availability.
- Appointment creation is one database transaction.
- The request is normalized before hashing; the hash is SHA-256 over canonical JSON.
- Idempotency records use scope `public:appointment:create:v1` and expire after 24 hours. This is a technical replay-retention window, not a clinic policy.
- Same key plus same normalized body replays the stored status/body within the 24-hour replay window.
- Same key plus a different normalized body returns `409 IDEMPOTENCY_CONFLICT`.
- A successful public booking starts as `pending` with a 1-hour hold, using the owner-approved Task 13 hold policy.
- Expired pending holds are conditionally changed to `expired` only while their status is still `pending`; practitioner/resource capacity rows are then deactivated.
- A just-confirmed appointment cannot be expired by the conditional update.

## Capacity and concurrency
- Availability is re-checked immediately before the write.
- Appointment practitioner/resource occupancy ranges include the service pre/post buffers.
- Existing PostgreSQL GiST exclusion constraints are the final atomic race guard.
- A conflict returns only `SLOT_UNAVAILABLE` with HTTP 409 and no internal database error details.
- A PostgreSQL migration backfills existing occupancy ranges with service buffers and adds a database trigger for the approved appointment state-transition graph.
- The concurrency proof uses 50 simultaneous real PostgreSQL worker processes for one slot; acceptance requires exactly one 201 result and 49 SLOT_UNAVAILABLE results.

## State and audit
Allowed transitions:
- pending → confirmed, cancelled, expired
- confirmed → completed, cancelled, no_show
- completed/cancelled/no_show/expired → terminal

Creation and state changes write AppointmentEvent records. Sensitive booking mutations also write minimal audit metadata without patient name/mobile values.

## Deliberate omissions
- Public booking does not merge patients by mobile number; each distinct booking request creates its own patient record, while idempotency prevents duplicate retries.
- No automatic Iranian holiday policy.
- The existing UI was simulated at Task 14; Task 16 now replaces that behavior in its local patch.
- No production migration/seeding/deployment.
- No public appointment mutation/cancellation flow in this task.
