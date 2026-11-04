# Phone verification provider seam and Task 15 implementation

**Status: implementation added on the test branch; provider activation remains an explicit deployment step.**

The owner supplied a MeliPayamak OTP endpoint for testing. The endpoint token is a secret and is intentionally not stored in this repository. Configure the full HTTPS endpoint URL in the runtime secret store as `MELIPAYAMAK_OTP_URL`. `.env.example` contains only the blank variable name.

## Public booking flow

1. The public form sends the normalized mobile number plus the exact booking intent (name, service and start time) to `POST /api/v1/phone-verification/send`.
2. The backend sends the OTP through `MeliPayamakOtpProvider`, stores only a password hash of the provider-returned code, and returns a short-lived challenge ID.
3. The user submits the code to `POST /api/v1/phone-verification/verify`. The backend returns a random, short-lived verification grant only after a successful server-side comparison.
4. Appointment creation requires that grant in `X-Phone-Verification-Grant`. The grant is bound to the normalized phone and canonical booking intent, then consumed under the same database transaction as appointment creation.
5. A grant cannot be used for another phone, another service/time/name, another purpose, or a second booking.

The challenge lifetime is five minutes, the maximum number of incorrect code attempts is five, and the resend cooldown is 60 seconds. Provider requests use Laravel's HTTP client with TLS verification enabled and a bounded timeout. Provider response details, codes and phone numbers are not returned to the browser or written to application logs.

## Staff login

The existing email/password login remains available. A separate optional OTP flow is exposed on the staff login page. A staff account can use it only after a trusted administrator has populated its `mobile_normalized` field with a number already associated with that staff member. The login response does not disclose whether a phone number belongs to an active staff account; OTP challenges use a distinct `staff_login` purpose and cannot be used for public booking.

## Configuration and safe testing

- Set `MELIPAYAMAK_OTP_URL` in local/deployment secrets only; never commit the actual endpoint token.
- Do not disable TLS certificate verification.
- Automated tests fake the HTTP provider and use synthetic phone numbers; they must never send a real SMS.
- Public appointment creation remains fail-closed unless a valid OTP grant is present.
- No real SMS was sent as part of repository changes. Live provider testing must be performed manually with an authorized test phone after local secret configuration.
- The test endpoint was pasted into a chat and should be rotated before production use.
- Staff mobile enrollment and production retention/cleanup policies still need operational approval before production rollout.

## Security notes

Codes are stored only as hashes. Grants are random, stored as hashes, purpose-specific, phone-specific, intent-specific, short-lived and single-use. Rate limits apply to send/verify routes and resend attempts. All public endpoints return generic errors and no-store cache headers where appropriate. The existing password login is not removed or weakened.
