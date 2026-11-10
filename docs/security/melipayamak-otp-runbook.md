# MeliPayamak OTP provider runbook

## Scope

MeliPayamak is configured as the provider-generated SMS OTP adapter for public booking phone verification. It is separate from transactional appointment reminders and is not used for staff MFA. Staff MFA remains TOTP plus one-time recovery codes.

The adapter is `App\\Notifications\\Otp\\MeliPayamakOtpProvider`, resolved through `OtpProviderManager`. It sends a JSON POST containing only the normalized Iranian mobile number (`{"to":"09..."}`) to the fixed HTTPS API endpoint. The provider returns the OTP in `code`; a non-empty `status` is treated as a provider error. The code is consumed server-side and only its HMAC-SHA-256 digest is stored.

## Local/staging activation

The repository deliberately keeps live SMS disabled in its committed defaults and in CI. Activation requires **both explicit approval flags** in the ignored environment file:

```dotenv
SMS_OTP_DRIVER=melipayamak
OTP_LIVE_SMS_APPROVED=true
OTP_PROVIDER_URL_PATH_RISK_ACCEPTED=true
MELIPAYAMAK_OTP_TOKEN=<the token segment after /api/send/otp/ in the supplied private endpoint>
```

For a local controlled test, the URL-path risk flag is an explicit acknowledgement of the provider contract; it does not mean production approval.

After changing environment variables:

```bash
php artisan config:clear
php artisan optimize:clear
php artisan serve
```

Use only a phone number controlled by the developer. The API issue endpoint is `POST /api/v1/booking-otp/issue` with JSON `{"mobile":"0912..."}`. The response contains a `challenge_id` and expiry only, never the OTP. Submit the received SMS code to `POST /api/v1/booking-otp/verify` with `{"challenge_id":"...","code":"..." }`.

If the provider call fails, the application returns a generic `OTP_UNAVAILABLE` response. Provider URLs, tokens, OTP values and provider error bodies are not returned to the browser or written to application logs.

Do not put the token into `.env.example`, source code, GitHub commits, issue comments, screenshots, browser JavaScript or CI artifacts. `.env` and `.env.*` are git-ignored. The integration cannot inject a secret into a developer's local filesystem; the local environment step above is required before live delivery can occur.

## Runtime and database requirements

- The committed default remains `SMS_OTP_DRIVER=null`, so a fresh clone and CI never send a real SMS.
- The local prototype defaults to SQLite and file cache. The OTP issuance path uses file-cache locks on SQLite to serialize local quota checks. SQLite is for a single-machine prototype only.
- PostgreSQL is the supported multi-node target. It uses transaction-scoped advisory locks for atomic phone/IP quotas. Do not deploy multi-node OTP issuance with SQLite or an array/in-memory cache.
- The endpoint is fixed to `https://console.melipayamak.com/api/send/otp/{token}`; callers cannot supply a URL or host.
- TLS certificate verification stays enabled. Do not copy the provider sample's `CURLOPT_SSL_VERIFYPEER=false`.
- Redirects are disabled, request timeout is bounded, and the adapter does not automatically retry sends because retries can create duplicate messages or charges.

## Security and OTP policy

- SMS/PSTN OTP is used only for **public booking phone possession/verification**, not staff MFA. NIST SP 800-63B-4 treats PSTN/SMS authentication as restricted and calls for risk assessment; staff accounts therefore use TOTP instead. citeturn884438search2
- Challenge lifetime: 5 minutes; maximum failed verification attempts: 5; resend cooldown: 60 seconds; phone limits: 3 sends per 15 minutes and 5 per hour; IP limit: 10 sends per hour.
- Phone and IP identifiers are stored as keyed digests. A new issuance invalidates prior pending/verified challenges. Verification is one-time and appointment creation consumes the challenge transactionally.
- The provider-generated OTP is never returned to the browser or persisted in plaintext. Only an HMAC-SHA-256 digest bound to the challenge ID is stored. This aligns with current OTP verifier guidance around one-time acceptance, protected collection and rate limiting. citeturn839205search2
- The returned provider code must be numeric (4–12 digits); provider-declared non-empty `status` is treated as failure.
- Because the MeliPayamak token is a URL path segment, production requires explicit provider/network log-redaction review and risk acceptance. Application code does not log provider request URLs. OWASP recommends that access tokens, secrets and sensitive personal data not be recorded directly in logs. citeturn280317search2turn280317search0
- Do not use real patient data during prototype testing.

## Verification boundary

Synthetic HTTP and feature tests verify request method, JSON payload, response parsing, provider error handling, redirect rejection, OTP hashing, expiry, attempt limits, resend quotas and one-time consumption. CI never calls MeliPayamak.

A developer must perform one controlled live test to a phone they own after configuring the local secret. That test is intentionally outside CI because CI must never receive or send with a real provider credential.

## Production activation boundary

Production requires all of the following before changing the committed/default `SMS_OTP_DRIVER=null` posture:

1. MeliPayamak vendor/data-flow approval by the clinic.
2. Provider/network confirmation of URL-path access-log retention and redaction, or selection of a provider with header-based authentication.
3. Secret-manager injection and rotation procedure for the provider token.
4. PostgreSQL primary and shared production cache/rate-limit infrastructure.
5. Trusted-proxy configuration so IP-based abuse limits see the real client IP.
6. Authorized staging abuse/replay/expiry tests.
7. HTTPS/TLS and production session-cookie verification.
8. A documented rollback: set `SMS_OTP_DRIVER=null`, remove the provider secret, clear cached config, and verify booking fails closed with `OTP_UNAVAILABLE` rather than silently bypassing phone verification.

This separation follows the security principle of keeping secrets out of source/logs and making activation/rotation explicit. citeturn280317search0turn280317search2
