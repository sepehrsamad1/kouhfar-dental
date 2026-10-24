# Architecture (Package 3)

Status: design, not built. Depends on docs/product-spec.md (Package 2) and docs/ui-audit.md (Package 1). Decisions: docs/adr/README.md.

## 1. Shape of the system
- **web**: current static site (Vanilla JS, lightweight build). Calls the API only through one api-client module. Owns no business rules.
- **api**: Express + TypeScript. Modular routes. All validation and authorization on the server.
- **worker**: same codebase, separate entry point. Runs expiry, notifications, image processing, AI usage rollups.
- **db**: PostgreSQL via Prisma. Booking conflicts enforced by database constraints (section 4).
- **storage**: private file store for images. Never served as public static files.

Frontend and backend develop against the same contract (docs/api/openapi.yaml). Either side can mock the other from that file alone.

## 2. Route separation
| Prefix | Audience | Auth | Notes |
|---|---|---|---|
| /api/public/* | Anonymous visitors | None, or follow-up token for own booking | Rate limited. No patient data. |
| /api/staff/* | Reception, practitioner | Staff session cookie + CSRF | Permission checks per endpoint. |
| /api/admin/* | Admin | Staff session, admin role | Separate router and middleware. |
| /health/live, /health/ready | Infra | None | Ready checks DB only. No details publicly. |

## 3. Modules
| Module | Responsibility | Depends on |
|---|---|---|
| auth | Staff login, sessions, CSRF, lockout | db |
| users | Staff accounts, roles, deactivation | auth, audit |
| patients | Master data, search, near-match flags, archive | audit |
| catalog | Services, practitioners, resources | audit |
| schedule | Working hours, exceptions, holidays | catalog |
| availability | Free-slot calculation (read only) | catalog, schedule, bookings |
| bookings | Lifecycle, atomic create and reschedule, expiry | availability, patients, idempotency |
| verification | OTP challenges (if D8) behind an SMS adapter | notifications |
| content | Public site content | media |
| media | Private upload, validation, consent link | storage, audit |
| ai | Assistant and smile simulation, usage caps | content |
| jobs | Background queue (Postgres-backed) | db |
| audit | Append-only audit writer | db |
| idempotency | Key storage and replay | db |

Rule: modules call each other through service functions, never each other's tables. Routes hold no business logic.

## 4. Booking core (database-level design)
Occupancy is stored as time ranges. The app does not check overlap by itself.
- Extension `btree_gist` is required for `EXCLUDE` combining equality and range overlap.
- `start_at`, `end_at` are computed on the server from the service duration. Client values are ignored.
- `occupied = tstzrange(start_at - buffer_before, end_at + buffer_after, '[)')`, computed on the server.
- `AppointmentPractitioner(appointment_id, practitioner_id, occupied, active)`: `EXCLUDE USING gist (practitioner_id WITH =, occupied WITH &&) WHERE (active)`.
- `AppointmentResource(appointment_id, resource_id, occupied, active)`: `EXCLUDE USING gist (resource_id WITH =, occupied WITH &&) WHERE (active)`.
- `active` is true only for `pending` and `confirmed`. It changes in the same transaction as the status.
- Create, confirm, and reschedule are single transactions. A violation (SQLSTATE 23P01) returns 409 SLOT_UNAVAILABLE.
- Reschedule: insert new occupancy rows and deactivate old ones in one transaction. A conflict rolls back and the original stays.
- Expiry: job sets `status = expired`, `active = false` where status is still pending and `expires_at < now()`. Idempotent by that guard.

Rejected: "SELECT overlapping, then INSERT" (race window; ADR-0002). Availability uses the same ranges with `&&`, so read and write paths agree.

## 5. Sessions and auth
- Staff sessions are server-side rows. Cookie holds an opaque random token; DB stores its SHA-256 hash.
- Login rotates the session. Logout and deactivation revoke all sessions for that user.
- Cookie: HttpOnly, SameSite=Lax, Secure in production.
- CSRF: double-submit token bound to the session, required on every state-changing staff or admin request.
- Patient follow-up: separate short-lived token scoped to one booking and one action. Not a staff session.

## 6. Idempotency
- Header `Idempotency-Key` required on booking, transition, and create endpoints.
- Stored with owner scope (client fingerprint for public, user ID for staff), request hash, response snapshot, 24 h expiry.
- Same key and hash: stored response. Same key, different hash: 422 IDEMPOTENCY_KEY_REUSED.
- Duplicate while first is in flight: 409 REQUEST_IN_PROGRESS.

## 7. Background processing
- Postgres job table, claimed with `FOR UPDATE SKIP LOCKED`. Retry with backoff. Dead-letter after N attempts.
- Scheduled: pending expiry, outbox sends, image processing, AI usage rollup, cleanup.
- Every job idempotent. A restart neither loses nor double-runs work.

## 8. Private image storage
- Upload through the API. Type checked by magic bytes, not extension. EXIF stripped. Re-encoded to a fixed format.
- Stored outside the web root under a random UUID key. No public URLs.
- Read only through an authorized streaming endpoint. Case images need admin role and an active ConsentRecord.
- Backend: private local volume for dev and staging. Production choice open (ADR-0005).

## 9. AI cost control
- Every AI call writes AIRequest and AIUsage (feature, tokens, cost estimate, actor).
- Daily and monthly caps. Hard stop returns a safe fallback, not an error page.
- Public assistant rate limited per client. Answers only from published ContentItem records (R-AI-02).
- Smile output labeled as simulation (R-AI-03). Admin-only kill switch. Provider keys never in the repo or logs.

## 10. Data deletion
- Default is archive. Archiving a patient keeps appointments intact.
- Hard deletion requires an approved retention policy (D11), a DeletionRequest, and approval by a second admin, plus an audit entry.
- Images: deletion removes the file and marks the MediaAsset deleted, leaving an audit tombstone with no content.
- No hard-delete endpoint exists until D11 is approved.

## 11. Logging, errors, limits
- Structured logs with request ID. Redacted: passwords, tokens, cookies, phone numbers, notes, OTP codes.
- Standard error envelope (openapi.yaml). No stack traces or DB details to clients.
- Body size limit per route. Rate limits per route group. CORS: same origin in production.

## 12. Open items
- Exact framework and ORM versions: pinned in Package 4 with a lockfile.
- SMS provider (D8, D13), AI provider (D13), production object storage (ADR-0005).
