# Project status

Last updated: 2026-10-28.

| Package | Status | Notes |
|---|---|---|
| 1 Shell audit | Done (docs only) | Original browser screenshots were not taken. |
| 2 Requirements | Draft, partially superseded by later owner decisions | See `docs/product-spec.md`. |
| 3 Architecture and contract | Done (design) | Original OpenAPI draft has planned endpoints that differ from the current Task 13/14 routes. |
| 4 Repo setup | Scaffold done | Repository is active; settings still need owner verification. |
| 5 Modularize UI | VERIFIED | See task 05 documentation. |
| 6 CI, testing, and developer tooling | VERIFIED | PR #1 merged; CI passed. |
| 7 Core Eloquent data model | Complete for approved scope (OTP deferred) | OTP and VerificationChallenge remain deferred to Task 15. |
| 8 Database migrations, seeders, and constraints | VERIFIED | PostgreSQL schema and tests implemented; see Task 08 status. |
| 9 Staff authentication | VERIFIED | See Task 09 status; CI passed. |
| 10 Authorization and audit controls | VERIFIED | See Task 10 status; CI passed. |
| 11 Staff patient management | VERIFIED | CI passed on main. |
| 12 Services, practitioners, resources, and working hours | MERGED; CI VERIFIED | PR #13 merged to main. |
| 13 Availability engine and timezone-correct slot generation | MERGED; CI VERIFIED | PR #14 merged to main; post-merge CI passed. |
| 14 Transactional appointment creation | MERGED; CI VERIFIED | PR #15 merged to main as `9920f2b9dc80352412921bff20c8241f61c7f49e`; post-merge CI passed. |
| 15 Public booking verification/tracking | DEFERRED | Owner confirmed mobile OTP is required. SMS provider/subscription, budget, privacy consent wording/version, and operational verification policy are still being gathered. Do not implement the unresolved provider/policy layer yet. |
| 16 Connect existing booking UI to real API | MERGED; CI VERIFIED | PR #17 merged to main as `2ad1e590e93eaaa38965cfbfeff660c0a9f47cf2`. CI run `40740015793` passed after merge. |

## Decisions still requiring owner confirmation

- Practitioner identities, qualifications, and practitioner-to-service assignments.
- Exact recurring breaks and holiday dates; public holidays are not automatically clinic closures.
- Task 15 SMS provider/subscription, budget, privacy notice text/version, verification-grant policy, anti-abuse policy and tracking/status verification policy.

Confirmed: two dental units shared between practitioners and one consultation space; clinic hours 12:00-20:00 Saturday-Thursday in `Asia/Tehran`; cross-midnight working periods prohibited.

Task 13/14 booking policies:
- Pending appointment hold: 1 hour.
- Minimum booking notice: 24 hours.
- Booking horizon: through the end of the local date 14 days after the current local date.

Task 16 boundary:
- Public booking UI is connected to the real Task 13/14 APIs.
- OTP remains fail-closed/default-on until Task 15 supplies the approved verification implementation.
- No SMS provider, real message, provider credential, privacy notice, or real patient data was introduced.
