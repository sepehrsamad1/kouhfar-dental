# Architecture Decision Records (Package 3)

Kept in one file for this package. Split into one file per ADR when the repo exists (Package 4).

| ID | Title | Status |
|---|---|---|
| ADR-0001 | Express + TypeScript + Prisma + PostgreSQL, modular monolith | Accepted (design) |
| ADR-0002 | Booking conflicts enforced by PostgreSQL exclusion constraints | Accepted (design) |
| ADR-0003 | Server-side sessions with hashed tokens | Accepted (design) |
| ADR-0004 | Postgres-backed job queue, no Redis yet | Accepted (design) |
| ADR-0005 | Private image storage: local volume now, object storage later | Proposed (owner input needed) |
| ADR-0006 | Route separation by audience (public, staff, admin) | Accepted (design) |
| ADR-0007 | Idempotency keys scoped per owner | Accepted (design) |

## ADR-0001: Stack and modular monolith
Accepted (design). Versions pinned in Package 4 with a lockfile.
Decision: one Express + TypeScript API codebase with modules, Prisma, PostgreSQL. Frontend stays Vanilla JS with lightweight build (Package 5). Worker is a second entry point of the same codebase.
Why: small team, one database, booking consistency needs one transaction boundary.
Rejected: microservices (operational cost too high); React/Next.js migration (violates rule 4 without approval); NestJS (heavier than needed).
Revisit when: one API process cannot meet load, or a second team owns a module.

## ADR-0002: Booking conflicts enforced by database exclusion constraints
Accepted (design).
Decision: per-practitioner and per-resource `tstzrange` occupancy with `EXCLUDE USING gist` WHERE active. Requires `btree_gist`. Create, confirm, reschedule are single transactions. Violation maps to 409 SLOT_UNAVAILABLE.
Why: the only option that holds under concurrency without trusting application code.
Rejected: "SELECT then INSERT" (race window; disallowed by the Package 3 prompt); advisory locks only (any code path that skips the lock can overbook; optional extra, not the guarantee); serializable isolation alone (retries under load, easy to misuse).
Consequences: occupancy rows and status change in one transaction. The hosting provider must allow `btree_gist` (Package 31 risk).

## ADR-0003: Server-side sessions with hashed tokens
Accepted (design).
Decision: opaque random token in HttpOnly cookie; SHA-256 hash stored. Rotated on login; revoked on logout and deactivation. CSRF double-submit bound to session.
Why: staff access must end immediately on deactivation. JWTs cannot be revoked without a lookup anyway.
Rejected: JWT in localStorage (XSS exposure, hard to revoke); long-lived JWT cookie (same revocation problem); patient follow-up as a staff session (wrong privilege).

## ADR-0004: Postgres-backed job queue
Accepted (design).
Decision: jobs in PostgreSQL, claimed with `FOR UPDATE SKIP LOCKED`, retries with backoff, dead-letter status. Expiry, outbox, image processing, AI rollups run here.
Why: one less service; expiry must survive restarts; shares the transaction with booking data.
Rejected: Redis + BullMQ (extra service for low volume); cron-only expiry (no retries, no visibility).
Revisit when: job volume or latency needs a dedicated broker.

## ADR-0005: Private image storage (PROPOSED, owner input needed)
Status: Proposed.
Proposal: private volume for dev and staging. Production: S3-compatible private bucket, server-side access only, no public ACLs.
Open: hosting, budget (D13), and whether images may leave the clinic's region. Real patient images must never be used in tests or Git.
Rejected: public web root (leaks medical images); images in the database (bloats backups).

## ADR-0006: Route separation by audience
Accepted (design).
Decision: `/api/public`, `/api/staff`, `/api/admin` are separate routers with separate middleware. Admin is not a flag on staff routes.
Why: makes accidental exposure of admin endpoints harder; reviewers can check each surface in one place.
Rejected: one router with per-route role checks only (easy to miss on new endpoints). Role checks still required on every endpoint.

## ADR-0007: Idempotency keys scoped per owner
Accepted (design).
Decision: `Idempotency-Key` required on state-changing POSTs and transitions. Stored with owner scope, request hash, response snapshot, 24 h expiry. Same key with a different payload returns 422.
Why: protects against double clicks and dropped connections (Package 14 acceptance).
Rejected: dedupe by payload hash alone (collapses legitimate identical requests); no keys (duplicates on retry).
