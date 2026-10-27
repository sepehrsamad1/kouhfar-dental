# Task 14 — Atomic appointment creation

Status: IMPLEMENTED ON DRAFT BRANCH — merge pending owner approval.

## Branch and scope
- Branch: `task-14-appointment-creation`
- Public creation accepts only the existing booking UI fields: service, start time, full name, and mobile.
- Practitioner and resource are selected server-side from Task 13 availability.
- Appointment instants are stored in UTC; clinic schedule/availability remains `Asia/Tehran`.
- Public requests create `pending` only.
- Pending hold: 1 hour, approved in Task 13.
- Idempotency records expire after 24 hours.
- No payment, insurance, clinical-record fields, OTP, or real external provider was added.

## Implementation
- `app/Domain/Booking/AppointmentCreationService.php`: one outer PostgreSQL transaction, advisory-lock serialization for the idempotency key, availability re-check, patient creation, appointment creation, atomic practitioner/resource capacity reservation, event/audit writes, and replay snapshot.
- `app/Domain/Booking/AppointmentStateMachine.php`: single lifecycle transition module; terminal states deactivate capacity; expiry uses row locking plus conditional status update.
- `database/migrations/2026_10_27_000007_enforce_appointment_status_transitions.php`: PostgreSQL trigger rejects invalid appointment state transitions as defense in depth.
- Occupancy pivots store service buffers in `tstzrange`, so database exclusion protects the same interval used by availability.
- `routes/api.php`: versioned `POST /api/v1/booking/appointments`.
- `CreatePublicAppointmentRequest`: validates the idempotency key, service UUID, name, mobile and start time.
- Same idempotency key + same normalized body replays the stored 201 response; same key + different body returns HTTP 409 `IDEMPOTENCY_KEY_REUSED`.
- Capacity races return HTTP 409 `SLOT_UNAVAILABLE` without database/internal details.
- Anonymous public appointment events/audits use null actor IDs and minimal metadata.

## Tests
- PostgreSQL feature coverage creates pending appointments transactionally and verifies UTC times, 1-hour expiry, capacity rows, AppointmentEvent and idempotency persistence.
- Duplicate retry creates exactly one appointment.
- Same key with changed body returns 409.
- Expired idempotency keys can be reused after 24 hours.
- Expired pending capacity is transitioned to `expired` and deactivated before a new reservation.
- Lifecycle transition rules and PostgreSQL trigger are tested.
- Buffer occupancy is tested at the database level.
- A 50-process PostgreSQL concurrency test is included using `pcntl_fork`; it requires the real PostgreSQL test environment and asserts exactly one successful booking.

## Deliberate omissions
- The existing booking UI remains simulated; connecting it to this endpoint belongs to the later frontend/API integration task.
- OTP is not implemented because its provider/verification policy remains owner-dependent.
- No follow-up-token workflow, cancellation/rescheduling endpoint, payment, insurance, or clinical data was added.
- No production migration, seeding, deployment, or destructive database operation was performed.

## Open questions / assumptions
- Idempotency expiration is a technical 24-hour replay window required by the booking architecture.
- No public reference-code field exists in the current schema, so the creation response deliberately returns only the replayable pending status; the existing simulated tracking code is not persisted.
