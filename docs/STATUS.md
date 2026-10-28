# Project status

Last updated: 2026-10-28.

| Package | Status | Notes |
|---|---|---|
| 1 Shell audit | Done (docs only) | Original browser screenshots were not taken. |
| 2 Requirements | Draft, partially superseded by later owner decisions | See `docs/product-spec.md`. |
| 3 Architecture and contract | Done (design) | Contract was not validated against a running server. |
| 4 Repo setup | Scaffold done | Repository is active; settings still need owner verification. |
| 5 Modularize UI | VERIFIED | See task 05 documentation. |
| 6 CI, testing, and developer tooling | VERIFIED | PR #1 merged; CI passed. |
| 7 Core Eloquent data model | Complete for approved scope (OTP deferred) | OTP and VerificationChallenge deferred. |
| 8 Database migrations, seeders, and constraints | VERIFIED | PostgreSQL schema and tests implemented; see Task 08 status. |
| 9 Staff authentication | VERIFIED | See Task 09 status; CI passed. |
| 10 Authorization and audit controls | VERIFIED | See Task 10 status; CI passed. |
| 11 Staff patient management | VERIFIED | CI run #90 passed on main. |
| 12 Services, practitioners, resources, and working hours | MERGED; CI VERIFIED | PR #13 merged to `main` as `75f3b993ccba150ac4941407dcba4f1a84438d35`. CI run `40431587997` passed on code commit `9859a6a3ba7c03e5f9c9fa95a27a9ab77ad2acdd` (138 Laravel tests, 649 assertions, 5 Playwright tests). No production migration or seeding was run. |
| 13 Availability engine and timezone-correct slot generation | MERGED; CI VERIFIED | PR #14 merged to `main` as `89736b5a67ef6ddb2033ed806b90581cfdf4b993`. Post-merge CI run `40534205011` passed the full suite. |
| 14 Transactional appointment creation | MERGED; CI VERIFIED | PR #15 is merged to `main`; current main head `9920f2b9dc80352412921bff20c8241f61c7f49e` is the merge commit. One-transaction public booking, idempotency replay/conflict handling, buffered PostgreSQL capacity reservation, pending-hold expiry, state-transition controls, audit/events, and 50-attempt PostgreSQL concurrency proof. The recorded CI run `40540118213` passed: 161 Laravel tests, 23,581 assertions, full static/migration/browser checks. The older Task 14 status page was stale and has been reconciled.

## Decisions still requiring owner confirmation

- Practitioner identities, qualifications, and practitioner-to-service assignments.
- Exact recurring breaks and holiday dates; public holidays are not automatically clinic closures.
- Other product-spec decisions not explicitly superseded by later owner instructions.

Confirmed: two dental units shared between practitioners and one consultation space; clinic hours 12:00-20:00 Saturday-Thursday in `Asia/Tehran`; cross-midnight working periods prohibited.

Task 13 owner-approved booking policies used by Task 14:
- Pending appointment hold: 1 hour.
- Minimum booking notice: 24 hours.
- Booking horizon: through the end of the local date 14 days after the current local date.

D8 mobile OTP is now owner-confirmed as required (2026-10-28). The SMS provider/subscription, budget, copy and operational policies remain unresolved. Task 15 must implement and verify the grant flow; public appointment creation fails closed while `BOOKING_PHONE_VERIFICATION_REQUIRED=true`.

| 16 Connect existing booking UI to real API | Implemented in local patch; verification pending | Uses Task 13/14 APIs; adds minimal public service listing + versioned/redacted availability adapters; see `docs/task16-status.md`. D8 OTP is owner-confirmed as required; the SMS provider remains unselected and booking fails closed by default. Not pushed/merged; PHP/CI/browser visual checks remain pending. |
