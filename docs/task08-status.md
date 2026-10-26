# Task 8 — Database migrations, seeders, and constraints

Status: VERIFIED.

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

## Verification evidence
GitHub Actions CI run #40 (run ID 40390522856) completed successfully on commit `8b971a181d45bf9de05c9ecb9a26ee3221311060`.
The PostgreSQL CI job passed JavaScript lint/typecheck/build, PHP formatting, static analysis, database migrations, the Laravel test suite, Playwright Chromium installation, and browser smoke tests.

Local SQLite is not used as evidence for PostgreSQL exclusion constraints.

## Privacy/security
All seed values are synthetic. No real patient data or real credentials are included. Seed emails use the .invalid domain and generated random password hashes.

## Deliberate omission
OTP/VerificationChallenge remains deferred from Task 7 because its owner decision is unresolved.

## Rollback
Migrations are ordered from extensions through identity/catalog/booking/content/operations dependencies. A full rollback removes the Task 8 schema. Do not run rollback or destructive reset against production data.

## Next step
Merge PR #9 into main after the green CI verification above.
