# Task 16 — API integration contract (current implementation)

Task 16 runs in the public frontend track after Tasks 05/06; it does not wait for the full Task 15 package.
It consumes the already implemented Task 13 availability engine and Task 14 transactional booking endpoint.
The old OpenAPI 0.3 public paths (`/api/public/*`) are design-only and do not match the current Laravel routes.

## Routes used by the booking UI

| Method / path | Current implementation | Request / result |
|---|---|---|
| `GET /api/v1/services` | Added with Task 16 as a minimal public catalog endpoint | Returns `{data:[{id,name_fa,duration_minutes}]}` for active, public-bookable services only. The service UUID is a public booking reference; no patient/staff/capacity IDs are included. Empty list is valid and fail-closed. |
| `GET /api/v1/availability/{service}?date=YYYY-MM-DD` | Added versioned, date-scoped public adapter around Task 13 engine | Returns `{data:{date,timezone,slots:[{start_at,end_at}]}}`. IDs for practitioners/resources are never returned. `start_at` and `end_at` are UTC ISO-8601 instants. Response is `Cache-Control: no-store, private`. |
| `POST /api/v1/appointments` | Task 14 implementation | Body `{full_name,mobile,service_id,start_at}`; required `Idempotency-Key`; same-origin session and CSRF token. 201 public result `{status,start_at,end_at,expires_at}`. The internal `appointment_id` is stripped at the public controller boundary. Initial state is `pending`. |

The existing web availability route remains for compatibility and has also been redacted to omit practitioner/resource IDs.

## Error behavior

- Task 14 returns 409 top-level `{code:'SLOT_UNAVAILABLE'|'IDEMPOTENCY_CONFLICT',message}`.
- When `BOOKING_PHONE_VERIFICATION_REQUIRED=true` (default), Task 14 returns 503 `PHONE_VERIFICATION_NOT_READY` before creating an appointment. This is fail-closed until Task 15 implements the required grant flow.
- Laravel validation uses 422 `{message,errors:{full_name|mobile|service_id|start_at:[...]}}`.
- CSRF failure is 419; rate limit is 429; 5xx errors are normalized to safe generic copy.
- Client reads only known error codes and allowlisted field names. Server message values are discarded.
- Full Persian/English client copy is in `resources/js/booking/error-messages.js` and tested.

## Retry / idempotency

- GET: maximum 3 attempts (initial + 2), retry only network/timeouts, 502/503/504, or short 429 Retry-After. Timeout 10 seconds.
- POST: maximum 3 attempts, only on network/timeouts or 502/503/504. Timeout 15 seconds. Every attempt of one unchanged submission reuses the same `Idempotency-Key`; edits/slot changes get a new key. No retry on any 4xx or 500.
- Backoff: full jitter from exponential 350 ms base, capped at 3 seconds.
- The server's Task 14 transaction is the authoritative dedupe and capacity control. The browser never uses read-then-insert assumptions.

## Task 15 remains separate

No OTP endpoint, verification grant, privacy-consent version or appointment status lookup is implemented by Task 14. D8 is confirmed required; SMS vendor/budget remain unresolved. The default-on server gate rejects public creation until Task 15 adds verified grants. Task 16 does not collect extra consent or expose an internal UUID as a patient tracking code. Confirmation UI says the **request was received and is pending clinic approval**, only after the server returns a valid 201 appointment result. The internal `appointment_id` is removed from the public HTTP response; the client confirms only the returned status and requested slot.
