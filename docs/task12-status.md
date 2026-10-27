# Task 12 - Services, practitioners, resources, and working hours

Status: IN PROGRESS. Do not treat this task as verified until CI passes and the remaining acceptance criteria are closed.

## Repository review

- Tasks 07-10 models, migrations, authentication, Policies/Gates, and audit logging were inspected on `main`.
- Task 11 is merged to `main`; commit `ee82cb56bd7ed6b8f4422db3159f3a3a656b0e8d` and CI run #90 (40409553811) passed.
- Existing models/tables were extended instead of recreated.
- Implementation branch: `task/12-clinic-configuration`.

## Implemented so far

- Version fields for services, practitioners, resources, practitioner hours, and schedule exceptions.
- Clinic-wide recurring working hours and recurring breaks, both stored as local wall-clock time with `Asia/Tehran`.
- Nullable practitioner ownership for clinic-wide schedule exceptions.
- Staff configuration page and authenticated staff routes for services, practitioners, resources, clinic hours, practitioner hours, breaks, and schedule exceptions.
- Admin-only configuration writes based on the existing Task 10 role matrix; reception/practitioner can read the settings.
- Service name, duration, buffer-before, buffer-after, active status, and optimistic version check. Practitioner and resource display/type/active settings also have optimistic version checks.
- New services, practitioners, and resources are initially inactive. No fictional catalog, practitioner, resource inventory, or practitioner-to-service assignment is seeded. The existing non-production-only `DatabaseSeeder` now initializes the owner-confirmed clinic-wide 12:00-20:00 schedule for Saturday through Thursday using `firstOrCreate`, so rerunning it does not overwrite staff edits. Production seeding remains disabled.
- Configuration changes write allowlisted before/after audit metadata without patient information. Closure/holiday exceptions and recurring breaks are blocked when they overlap confirmed appointments. Service duration/buffer changes and practitioner/resource deactivation are blocked when future confirmed appointments reference them. No appointments are automatically modified.
- Feature tests cover role authorization, invalid cross-midnight periods, stale service version, appointment preservation, closure impact, and timezone conversion. Add/extend tests for every schedule edit/delete impact path before final verification.

## Owner-confirmed values

- Clinic location: Tehran.
- Base clinic hours requested: 12:00-20:00, Saturday through Thursday.
- Local schedule timezone: `Asia/Tehran`.
- Cross-midnight working periods are prohibited.
- Breaks, holidays, durations, and buffers are configurable.

## Owner decisions still open

- Resource allocation rules: `placeholder — owner to confirm`.
- Service catalog/default durations and buffers are not seeded.
- Practitioner qualifications and service assignments are not assumed.
- Exact recurring break times and holiday dates are not seeded.
- No resource conflict enforcement or booking availability engine is implemented.

## Known incomplete acceptance criteria

- Versioned edit/delete controls now exist for clinic hours, practitioner hours, breaks, and exceptions. Review the implementation and CI before marking acceptance criteria verified.
- Schedule impact handling and conflict validation need broader automated coverage, including recurring breaks, exception overlaps, and all relevant appointment states.
- No visual browser checks at 375px, 768px, and 1440px have been run for this new staff screen.
- GitHub Actions run `40425347718` passed on code commit `42ad11761afeaae56965dca3749b6acbcb100419`: JavaScript lint/typecheck/build, Pint, PHPStan, PostgreSQL migrations, 129 Laravel tests (604 assertions), and 4 browser smoke tests all passed.
- The current staff screen uses the existing simple Blade panel style; it does not redesign the public website.

## Rollback and migration notes

Migration `2026_10_27_000003_add_task12_configuration_versioning.php` adds version columns and clinic schedule tables and makes schedule exceptions clinic-wide-capable. Rollback intentionally refuses to make `practitioner_id` non-null if clinic-wide exceptions have been saved. Resolve those records explicitly before rollback. No production migration has been run.

## Next step

Complete the remaining configuration edit/deactivation and impact-check paths, then run `composer install`, `php artisan migrate --force` against the CI PostgreSQL service, `php artisan test`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`, `npm run lint`, `npm run typecheck`, `npm run build`, and `npm run test:browser` through CI. Update this file with actual evidence only.
