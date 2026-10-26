# Project status

Last updated: 2026-10-26.

| Package | Status | Notes |
|---|---|---|
| 1 Shell audit | Done (docs only) | Browser screenshots were not taken for the original audit. |
| 2 Requirements | Done (draft) | Owner decisions D1 to D16 remain open; synthetic-only seed values do not approve clinic policy. |
| 3 Architecture and contract | Done (design) | Contract was not validated against a running server. The data model specifies PostgreSQL exclusion constraints; the architecture overview still contains legacy Express/Prisma wording and should be reconciled in its own task. |
| 4 Repo setup | Scaffold done | GitHub repository is active. Repository settings still need owner verification. |
| 5 Modularize UI | VERIFIED | See docs/task05-visual-diff.md and docs/task05-status.md. |
| 6 CI, testing, and developer tooling | VERIFIED | Prior CI run passed 1/1 checks. CI runs PostgreSQL 16 and the full Laravel test suite. |
| 7 Core Eloquent data model | Complete for approved scope (OTP deferred) | Model classes and factories are present. OTP and VerificationChallenge are deliberately deferred. |
| 8 Migrations, seeders, and synthetic data | Implementation on `task/08-migrations-seeders`; CI verification pending | One ordered schema migration, PostgreSQL exclusion constraints, synthetic-only idempotent seeding, production guard, and constraint tests. Do not call verified until branch CI passes. |

## Task 08 report

- **Task:** Task 08: Migrations, Seeders, and Synthetic Data.
- **Branch:** `task/08-migrations-seeders`.
- **Commit SHA:** pending.
- **Summary:** Adds schema for the Task 07 entities in FK-safe order; uses restrictive foreign keys for patient and appointment history; adds uniqueness, indexes, check constraints, and PostgreSQL `btree_gist` exclusion constraints over half-open `tstzrange` occupancy for practitioners and resources. OTP schema remains excluded as directed by Task 07 status.
- **Synthetic data:** The seeder uses reserved `.invalid` email addresses, an all-zero non-routable phone placeholder, inactive staff/service/practitioner/resource fixtures, and one completed appointment dated in the past. It contains no usable credentials and cannot create active/bookable clinic entries. D1-D16 remain open; fixture durations and hours are not clinic policy.
- **Tests:** CI commands are `php artisan migrate --force` and `php artisan test`. Feature tests cover schema presence, appointment interval/expiry constraints, both overlap exclusions, seed idempotency, and production-seed refusal. CI execution and local execution have not yet been observed.
- **Acceptance evidence:** Fresh PostgreSQL migration, seeded-repeatability, constraints, and production guard are pending the actual CI run. No acceptance criterion is marked verified before evidence.
- **Security/privacy:** No real patient data, phone numbers, credentials, or external services. Synthetic staff are inactive and receive an unguessable random password hash that is not output or logged. No patient text is logged. Production seeding throws before inserting rows.
- **Open decisions:** D1-D16 remain unresolved. No clinic-specific values or behavior are inferred. `Asia/Tehran` remains the configured local schedule timezone from the existing project baseline; appointment timestamps remain UTC. OTP/VerificationChallenge remain deferred.
- **Rollback:** `php artisan migrate:rollback --step=1` reverses the Task 08 schema and drops its tables. Use only against a disposable, empty development/test database; never run rollback or `migrate:fresh` against production or data-bearing environments without an approved recovery plan.
- **Deliberately omitted:** Appointment workflows, availability, auth, live provider integration, production seed records, and VerificationChallenge.
- **Exact setup commands** after configuring `.env` for PostgreSQL 16+:
  - `php artisan migrate`
  - `php artisan db:seed`
  - `php artisan migrate:fresh --seed` (disposable local/test database only)
  - `php artisan test`
  - `php artisan migrate:rollback --step=1` (disposable local/test database only)

## Blocking owner decisions

D1-D16 remain open. They do not authorize real clinic schedules, prices, practitioner/resource counts, or booking policy. OTP and VerificationChallenge remain deferred. Keep all demo fixtures inactive and synthetic until decisions are approved.
