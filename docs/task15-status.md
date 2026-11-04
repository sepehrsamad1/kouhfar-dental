# Task 15 — Phone verification and MeliPayamak OTP

**Implementation branch:** `task/15-melipayamak-otp-test`  
**Status:** implementation complete in the task branch; automated CI green; live provider test and production approval pending.

## Added

- MeliPayamak OTP adapter using Laravel HTTP client, HTTPS-only endpoint validation, TLS verification defaults and a 10-second timeout.
- `MELIPAYAMAK_OTP_URL` runtime configuration; no endpoint token committed.
- `verification_challenges` table and model with hashed codes/grants, purpose, attempts, expiry, verification and consumption timestamps.
- Public send/verify endpoints and booking UI controls.
- Booking grant binding to the exact normalized phone and booking intent; atomic single-use consumption during appointment creation.
- Optional staff OTP login alongside the existing email/password login.
- Separate `staff_login` and `public_booking` challenge purposes.
- Fake-provider feature tests with synthetic numbers, including exact-intent binding and single-use grant consumption.
- Browser smoke coverage for the full booking OTP path and staff password-login compatibility.

## Current policy defaults

- Code lifetime: 5 minutes.
- Maximum incorrect attempts: 5.
- Resend cooldown: 60 seconds.
- Public booking: verification grant required.
- Staff login: password remains available; OTP requires an administrator-populated staff mobile number.
- Automated tests: HTTP fake only; no real SMS.

## Validation status

- Latest repository CI run: **passed** (PHP formatting, PHPStan, database migrations, full Laravel tests, JS lint/typecheck/build, deterministic Task 27 evaluation and all 36 browser smoke tests).
- Automated OTP tests use fake HTTP responses and synthetic numbers only. They do not prove that MeliPayamak delivered an SMS to a real handset.
- No real SMS has been sent from this implementation yet.

## Remaining before production

- Rotate the OTP endpoint token that was exposed during initial setup. Never reuse that token.
- On a local or staging environment, set the newly rotated full endpoint URL in `MELIPAYAMAK_OTP_URL` inside `.env` or the deployment secret store. Do not commit it or paste it into chat.
- Set `BOOKING_PHONE_VERIFICATION_REQUIRED=true`, clear cached config with `php artisan config:clear`, and apply migrations on the test environment.
- Manually test delivery and code verification using an authorized real phone number on local/staging. Complete a test booking only in a non-production database.
- For staff OTP, populate `staff_users.mobile_normalized` for a staff account only after an administrator verifies ownership of that number, then test login and retain password login as fallback.
- Confirm provider contract, production budget, retention/cleanup policy and staff mobile enrollment procedure.
- Rotate the endpoint token exposed during initial testing before production.
- Review logs, rate limits, and abuse monitoring under production traffic.

No production deployment, real SMS send, or production credential activation has been performed.
