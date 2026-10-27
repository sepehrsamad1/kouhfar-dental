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
- Service name, duration, buffer-before, buffer-after, active status, required resource type (`dental_unit` or `consultation_room`), and optimistic version check. Changing required resource type is blocked when future confirmed appointments reference the service. Practitioner and resource display/type/active settings also have optimistic version checks.
- Newly created services, practitioners, and resources are initially inactive. `ClinicStarterConfigurationSeeder` is an explicit, idempotent seeder for 10 inactive starter services and the owner-confirmed resource inventory (2 shared dental units and 1 consultation space). It is not called automatically by `DatabaseSeeder`; do not run it in production without explicit owner approval. It creates no practitioners and no practitioner-to-service assignments.
- Configuration changes write allowlisted before/after audit metadata without patient information. Closure/holiday exceptions and recurring breaks are blocked when they overlap confirmed appointments. Service duration/buffer changes and practitioner/resource deactivation are blocked when future confirmed appointments reference them. No appointments are automatically modified.
- Feature tests cover role authorization, invalid cross-midnight periods, stale service version, appointment preservation, closure impact, and timezone conversion. Add/extend tests for every schedule edit/delete impact path before final verification.

## Researched starter catalog

These are configurable planning estimates, not clinical instructions or promises about treatment duration. All seeded services remain inactive and not public-bookable until clinic management reviews them. Buffer values are operational estimates selected for initial scheduling; public sources generally discuss treatment duration, not a universal buffer standard.

| Starter service | Duration (minutes) | Buffer before | Buffer after | Required resource type |
|---|---:|---:|---:|---|
| معاینه دوره‌ای | 20 | 0 | 5 | dental_unit |
| مشاوره اولیه و طرح درمان | 30 | 0 | 5 | consultation_room |
| جرم‌گیری و پاک‌سازی دندان | 40 | 0 | 10 | dental_unit |
| ترمیم دندان (پرکردگی) | 45 | 0 | 10 | dental_unit |
| درمان ریشه (عصب‌کشی) | 90 | 0 | 15 | dental_unit |
| کشیدن ساده دندان | 30 | 0 | 10 | dental_unit |
| کشیدن جراحی دندان | 60 | 0 | 15 | dental_unit |
| آماده‌سازی روکش | 75 | 0 | 10 | dental_unit |
| تحویل و تنظیم روکش | 45 | 0 | 10 | dental_unit |
| مشاوره زیبایی دندان | 30 | 0 | 5 | consultation_room |

Duration ranges were compared against public dental clinic guidance: routine check-ups about 10-20 minutes, cleaning about 20-45 minutes, fillings about 20-60 minutes, simple extraction about 20-40 minutes, surgical extraction about 45-90 minutes, crown preparation about 60-90 minutes, and root canal appointments commonly 60-120 minutes. Individual cases and local practice differ.

Sources consulted, including Persian-language clinic guidance and NHS patient information:
- Check-up estimate (15-30 minutes): https://salamatishoma.ir/articles/dental-checkup-routine/
- Root canal estimate (30-90 minutes per session): https://adenisdental.com/dental-services/teeth-denervation/
- Scaling estimate (30-60 minutes): https://adenisdental.com/dental-services/teeth-scaling/
- Extraction estimate (20-45 minutes): https://kakh-labkhand.net/blog/%D8%AA%D8%B1%D9%85%DB%8C%D9%85-%D9%88-%DA%A9%D8%B4%DB%8C%D8%AF%D9%86-%D8%AF%D9%86%D8%AF%D8%A7%D9%86-%D8%AF%D8%B1-%D8%AA%D9%87%D8%B1%D8%A7%D9%86/
- Crown treatment example: https://healifa.com/service/tooth-crown
- NHS root canal information noting appointments can last 1-2 hours or longer: https://www.nhs.uk/tests-and-treatments/root-canal-treatment/

The seeder creates two shared dental units and one consultation space as inactive resources. Service requirements refer to a resource type, not one dedicated physical unit. The actual conflict-safe selection of one available resource from the shared pool belongs to Task 13.

No practitioner records or service assignments are seeded because actual practitioner identities and qualifications must be supplied by clinic management. No recurring breaks or holiday closures are seeded because there is no universal clinic policy and the clinic may choose to open on some public holidays.

## Owner-confirmed values

- Clinic location: Tehran.
- Base clinic hours requested: 12:00-20:00, Saturday through Thursday.
- Local schedule timezone: `Asia/Tehran`.
- Cross-midnight working periods are prohibited.
- Breaks, holidays, durations, and buffers are configurable.

## Owner decisions still open

- Shared resource policy is confirmed: the two dental units are shared between practitioners; one consultation space exists. The new service `required_resource_kind` stores the needed resource type, but Task 13 must implement atomic allocation from the shared pool.
- Starter service catalog and duration/buffer estimates are seeded only by explicitly running `php artisan db:seed --class=ClinicStarterConfigurationSeeder`; all services/resources remain inactive and not public-bookable until reviewed.
- Practitioner identities, qualifications, and service assignments remain unconfigured because they are clinic-specific and cannot be inferred safely from generic research.
- Exact recurring break times and holiday dates remain unconfigured. Public holidays are not automatically treated as clinic closures.
- No resource conflict enforcement or booking availability engine is implemented.

## Known incomplete acceptance criteria

- Versioned edit/delete controls now exist for clinic hours, practitioner hours, breaks, and exceptions. CI must be rerun after the new resource-kind migration, seeder, and tests.
- Schedule impact handling and conflict validation need broader automated coverage, including recurring breaks, exception overlaps, and all relevant appointment states.
- No visual browser checks at 375px, 768px, and 1440px have been run for this new staff screen.
- Previous GitHub Actions run `40425347718` passed on code commit `42ad11761afeaae56965dca3749b6acbcb100419`: JavaScript lint/typecheck/build, Pint, PHPStan, PostgreSQL migrations, 129 Laravel tests (604 assertions), and 4 browser smoke tests. New resource-kind and starter-catalog changes have not yet passed CI.
- The current staff screen uses the existing simple Blade panel style; it does not redesign the public website.

## Rollback and migration notes

Migration `2026_10_27_000003_add_task12_configuration_versioning.php` adds version columns and clinic schedule tables and makes schedule exceptions clinic-wide-capable. Migration `2026_10_27_000004_add_required_resource_kind_to_services.php` adds nullable resource-type requirements for services. Rollback intentionally refuses to make `practitioner_id` non-null if clinic-wide exceptions have been saved. Resolve those records explicitly before rollback. No production migration has been run.

## Next step

1. Run the full GitHub Actions workflow after the resource-kind migration, starter seeder, and new tests; fix any failures.
2. Perform responsive visual checks at 375px, 768px, and 1440px, including the existing homepage hero-stat icons and Persian typography.
3. Keep practitioner/service assignments empty until clinic management enters real practitioner identities and confirms qualifications.
4. Do not run `ClinicStarterConfigurationSeeder` in production without explicit approval; review its inactive values first.
5. Keep the PR draft until CI, responsive checks, and connected-account review are complete.
