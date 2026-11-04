# Task 15 — Phone verification and MeliPayamak OTP

**Implementation branch:** `task/15-melipayamak-otp-test`  
**Status:** implementation in progress; not production-approved.

## Added

- MeliPayamak OTP adapter using Laravel HTTP client, HTTPS-only endpoint validation, TLS verification defaults and a 10-second timeout.
- `MELIPAYAMAK_OTP_URL` runtime configuration; no endpoint token committed.
- `verification_challenges` table and model with hashed codes/grants, purpose, attempts, expiry, verification and consumption timestamps.
- Public send/verify endpoints and booking UI controls.
- Booking grant binding to the exact normalized phone and booking intent; atomic single-use consumption during appointment creation.
- Optional staff OTP login alongside the existing email/password login.
- Separate `staff_login` and `public_booking` challenge purposes.
- Fake-provider feature tests with synthetic numbers.

## Current policy defaults

- Code lifetime: 5 minutes.
- Maximum incorrect attempts: 5.
- Resend cooldown: 60 seconds.
- Public booking: verification grant required.
- Staff login: password remains available; OTP requires an administrator-populated staff mobile number.
- Automated tests: HTTP fake only; no real SMS.

## Remaining before production

- Run the full PHP and frontend test/build suites in the repository CI environment.
- Manually test the provider using an authorized test phone after setting the secret endpoint URL outside Git.
- Confirm provider contract, production budget, retention/cleanup policy and staff mobile enrollment procedure.
- Rotate the endpoint token exposed during initial testing before production.
- Review logs, rate limits, and abuse monitoring under production traffic.

No production deployment, real SMS send, or production credential activation has been performed.
