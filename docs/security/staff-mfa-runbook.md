# Staff TOTP MFA runbook

## Policy

All staff accounts must use TOTP-based multi-factor authentication. SMS is not the staff MFA factor. Each account receives ten one-time recovery codes at enrollment.

Production defaults:

- `STAFF_MFA_REQUIRED=true`
- `STAFF_MFA_PENDING_TIMEOUT_MINUTES=10`
- TOTP: six digits, 30-second period, SHA-1 as defined by RFC 6238, one-step clock-drift window.
- Account-level MFA verification throttle: five failed attempts per five minutes.
- Recovery codes are single-use; only password hashes are persisted, and the list is encrypted at rest.

## First staff login / enrollment

1. Staff member signs in with their existing email and password.
2. If MFA is not enrolled, the app creates and encrypts a TOTP secret and starts a 10-minute pending-authentication session. The user is not authenticated into the staff area yet.
3. The enrollment page displays the secret and instructs the staff member to add it manually to a compatible authenticator application. The page uses `Cache-Control: no-store` and `Pragma: no-cache`.
4. The staff member enters the current six-digit TOTP code.
5. The server verifies the code, enables MFA, stores the last accepted TOTP counter to prevent replay, and creates ten one-time recovery-code hashes.
6. The recovery codes are displayed once in a no-store response. They are not stored in the session or database in plaintext. The staff member must store them securely offline before continuing.

## Subsequent login

1. Password verification starts a pending session; it does not authenticate the staff member.
2. The user must provide a valid TOTP code or an unused recovery code within ten minutes.
3. TOTP counter updates and recovery-code consumption are transactionally locked to prevent concurrent reuse.
4. Successful MFA completes login and creates the normal staff session registry entry.
5. Failed MFA attempts are recorded using hashed identifier/IP metadata only; OTP values and TOTP codes are never written to audit logs.

## Lost authenticator / exhausted recovery codes

There is currently no self-service reset or admin reset UI. Do not disable MFA globally or edit encrypted fields directly in production. Before staff rollout, the clinic must approve an audited administrator recovery process and identity-proofing procedure. Until that process is implemented and tested, a staff member who loses their authenticator and all recovery codes may be unable to regain access.

## Deployment checks

- Keep `STAFF_MFA_REQUIRED=true` in production. Do not copy the CI-only false setting into production.
- Keep `APP_KEY` stable and protect it; rotating it without a planned re-encryption process makes encrypted TOTP secrets and recovery-code arrays unreadable.
- Serve all staff authentication pages over HTTPS. Keep secure session cookies enabled.
- Confirm database backups include encrypted MFA fields and restrict backup access.
- Do not log TOTP secrets, recovery codes, provisioning URIs, passwords or raw OTP values.
- CI sets `STAFF_MFA_REQUIRED=false` only for legacy browser screenshot fixtures; the dedicated `StaffMfaTest` suite explicitly enables MFA and verifies enrollment, RFC 6238 behavior, replay rejection and one-time recovery-code use.
