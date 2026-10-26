# Task 8 — Database migrations, seeders, and constraints

Status: In verification.

## Scope implemented
- PostgreSQL citext and btree_gist extensions.
- Identity/session/audit tables.
- Patient, service, practitioner, resource, schedule, and match-flag tables.
- Appointment/event/follow-up tables.
- PostgreSQL tstzrange occupancy and GiST exclusion constraints for active practitioner/resource bookings.
- Content, media, consent, AI request/usage tables.
- Jobs, notification outbox, idempotency, and deletion-request tables.
- Factories for all current Eloquent models.
- Production-guarded synthetic development seeder.
- Feature tests for schema presence, PostgreSQL overlap protection, adjacency, and repeatable seeding.

## Commands
Fresh development/test database: `php artisan migrate:fresh --seed`
Migrate: `php artisan migrate`
Rollback: `php artisan migrate:rollback`
Task 8 tests: `php artisan test --filter=Task08DatabaseTest`
Full test suite: `php artisan test`

Production seeding is deliberately blocked by DatabaseSeeder.

## Verification gate
Task 8 is not marked VERIFIED until GitHub Actions completes against PostgreSQL and the relevant tests pass. Local SQLite is not sufficient evidence for PostgreSQL exclusion constraints.

## Privacy/security
All seed values are synthetic. No real patient data or real credentials are included. Seed emails use the .invalid domain and generated random password hashes.

## Deliberate omission
OTP/VerificationChallenge remains deferred from Task 7 because its owner decision is unresolved.

## Rollback
Migrations are ordered from extensions through identity/catalog/booking/content/operations dependencies. A full rollback removes the Task 8 schema. Do not run rollback or destructive reset against production data.

## Next step
Run/inspect the PostgreSQL GitHub Actions checks. If green, update this document and the project status to VERIFIED.