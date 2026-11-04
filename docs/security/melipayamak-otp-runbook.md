# MeliPayamak OTP provider runbook

## Scope

MeliPayamak is the purchased provider for provider-generated SMS OTP delivery. It is separate from the existing transactional SMS notification adapter and is not used as the staff MFA factor. Staff MFA uses TOTP plus one-time recovery codes.

The adapter is implemented at `App\Notifications\Otp\MeliPayamakOtpProvider` and is resolved through `OtpProviderManager`. It posts JSON containing only the normalized Iranian mobile number to the fixed HTTPS provider endpoint and returns the provider-generated `code` to its server-side caller.

## Configuration

Set these only in the deployment secret manager / server environment:

- `SMS_OTP_DRIVER=melipayamak`
- `MELIPAYAMAK_OTP_TOKEN` = the provider token from the private endpoint path

Do not store the full token-bearing endpoint in source control, logs, tickets, screenshots, or client-side code. The repository example leaves the token empty and keeps the driver disabled.

## Security requirements

- Keep TLS certificate verification enabled. Never copy the provider's sample `CURLOPT_SSL_VERIFYPEER=false` setting.
- Redirects are disabled; the adapter uses a fixed HTTPS host and does not accept a caller-supplied endpoint.
- The adapter does not retry a send automatically because a retry could create duplicate messages or charges.
- The returned code is a server-side verification value. Never return it to the browser, write it to application logs, include it in exception text, or expose it to support staff.
- Persist only an HMAC-SHA-256 digest of the provider code in the OTP challenge record; phone and IP identifiers are also keyed digests. No plaintext OTP is stored.
- Challenge lifetime: 5 minutes; maximum failed verification attempts: 5; resend cooldown: 60 seconds; phone issuance limits: 3 per 15 minutes and 5 per hour; IP issuance limit: 10 per hour. These values were approved for this implementation on 2026-11-04.
- A new issuance invalidates all prior pending/verified challenges for that phone. Verification is one-time and the challenge is consumed in the same database transaction that creates the appointment.
- Normalize Iranian phone numbers before calling the adapter.
- Keep the provider token out of Git and rotate it if it is ever exposed outside approved secret handling.
- Because the token is a path segment, obtain provider confirmation of access-log retention/redaction and review any reverse-proxy/network logs before enabling live sends. The repository cannot verify provider-side logging or retention.

## Current implementation boundary

The provider adapter and HTTP contract tests are present. No live provider call has been made. The public booking controller continues to fail closed with `PHONE_VERIFICATION_NOT_READY` while the booking OTP challenge lifecycle is not yet implemented. Do not enable `SMS_OTP_DRIVER=melipayamak` in production until the challenge expiry, attempt/resend limits, storage and verification flow are implemented and tested.

The OTP challenge lifetime and attempt/resend policy must be confirmed before implementing that flow; they are not inferred from the provider's sample response. The provider sample does not specify code expiry or retry limits.
