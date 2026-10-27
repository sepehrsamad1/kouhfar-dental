# Task 14 — Transactional appointment creation

Status: VERIFIED ON DRAFT BRANCH — merge pending owner approval.

## Branch and scope
- Branch: `task/14-appointment-creation`
- PR: #15
- Keep the PR Draft until CI passes and the owner reviews the implementation.
- Public booking endpoint: `POST /api/v1/appointments`.
- PostgreSQL is authoritative for capacity reservation and appointment state invariants.

## Booking contract
- Request requires patient full name, Iranian mobile number, service, practitioner, resource, start instant and an `Idempotency-Key` header.
- Appointment creation is one database transaction.
- The request is normalized before hashing; the hash is SHA-256 over canonical JSON.
- Idempotency records use scope `public:appointment:create:v1` and expire after 24 hours. This is a technical replay-retention window, not a clinic policy.
- Same key plus same normalized body replays the stored status/body.
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
- No payment, insurance, clinical-record, diagnosis, notification-provider or messaging-provider integration.
- No automatic Iranian holiday policy.
- No production migration/seeding/deployment.
- No public appointment mutation/cancellation flow in this task.
