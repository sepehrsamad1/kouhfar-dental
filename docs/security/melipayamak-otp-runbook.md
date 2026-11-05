# MeliPayamak OTP provider runbook

## Scope

MeliPayamak is configured as the provider-generated SMS OTP adapter for public booking phone verification. It is separate from transactional appointment reminders and is not used for staff MFA. Staff MFA remains TOTP plus one-time recovery codes.

The adapter is `App\\Notifications\\Otp\\MeliPayamakOtpProvider`, resolved through `OtpProviderManager`. It sends a JSON POST containing only the normalized Iranian mobile number (`{"to":"09..."}`) to the fixed HTTPS API endpoint. The provider returns the OTP in `code`; a non-empty `status` is treated as a provider error. The code is consumed server-side and only its HMAC-SHA-256 digest is stored.

## Local prototype activation

The repository deliberately keeps live SMS disabled in its committed defaults and in CI. To enable the provider on the developer's own local machine:

1. Copy `.env.example` to `.env` if this has not already been done.
2. In the local, ignored `.env` file only, set:

   ```dotenv
   SMS_OTP_DRIVER=melipayamak
   MELIPAYAMAK_OTP_TOKEN=<the token segment after /api/send/otp/ in the supplied private endpoint>
   ```

3. Clear cached configuration and restart the local Laravel server:

   ```bash
   php artisan config:clear
   php artisan optimize:clear
   php artisan serve
   ```

4. Use the booking phone-verification flow with a phone number controlled by the developer. The API issue endpoint is `POST /api/v1/booking-otp/issue` with JSON `{"mobile":"0912..."}`. The response contains a `challenge_id` and expiry only, never the OTP. Submit the received SMS code to `POST /api/v1/booking-otp/verify` with `{"challenge_id":"...","code":"..." }`.
5. If the provider call fails, the application returns a generic `OTP_UNAVAILABLE` response. Provider URLs, tokens, OTP values and provider error bodies are not returned to the browser or written to application logs.

Do not put the token into `.env.example`, source code, GitHub commits, issue comments, screenshots, browser JavaScript or CI artifacts. `.env` and `.env.*` are git-ignored. The integration cannot inject a secret into a developer's local filesystem; the local environment step above is required before live delivery can occur.

## Runtime and database requirements

- The committed default remains `SMS_OTP_DRIVER=null`, so a fresh clone and CI never send a real SMS.
- The local prototype defaults to SQLite and file cache. The OTP issuance path uses file-cache locks on SQLite to serialize local quota checks. SQLite is for a single-machine prototype only.
- PostgreSQL is the supported multi-node target. It uses transaction-scoped advisory locks for atomic phone/IP quotas. Do not deploy multi-node OTP issuance with SQLite or an array/in-memory cache.
- The endpoint is fixed to `https://console.melipayamak.com/api/send/otp/{token}`; callers cannot supply a URL or host.
- TLS certificate verification stays enabled. Do not copy the provider sample's `CURLOPT_SSL_VERIFYPEER=false`.
- Redirects are disabled, request timeout is bounded, and the adapter does not automatically retry sends because retries can create duplicate messages or charges.

## Security and OTP policy

- The token is a credential even though it appears in the endpoint path. Treat it as secret and rotate it if exposed outside the intended provider account.
- Because the token is a path segment, the provider or an upstream proxy may record it in access logs. Review provider-side log retention/redaction and network/proxy logging before production activation. The application cannot verify the provider's logging behavior.
- Never return, log, persist in plaintext, or expose the provider-generated OTP. The application stores only an HMAC digest bound to the challenge ID.
- Challenge lifetime: 5 minutes; maximum failed verification attempts: 5; resend cooldown: 60 seconds; phone limits: 3 sends per 15 minutes and 5 per hour; IP limit: 10 sends per hour.
- Phone and IP identifiers are stored as keyed digests. A new issuance invalidates prior pending/verified challenges. Verification is one-time and appointment creation consumes the challenge transactionally.
- The returned provider code must be numeric (4–12 digits); provider-declared non-empty `status` is treated as failure even if a `code` field is present.
- Do not use real patient data during prototype testing.

## Verification boundary

Synthetic HTTP and feature tests verify request method, JSON payload, response parsing, provider error handling, redirect rejection, OTP hashing, expiry, attempt limits, resend quotas and one-time consumption. CI never calls MeliPayamak. No live SMS has been sent by this engineering change; a developer must perform one controlled test to a phone they own after configuring the local secret.

Local prototype activation is distinct from production approval. Production still requires clinic/legal vendor and data-flow approval, confirmation of provider-side URL-path logging/retention, secret-manager injection, staging tests and the deployment controls listed under SEC-28-006 / SEC-28-015.
