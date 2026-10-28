# Phone verification provider seam (preparation only)

**Provider workflow not implemented.** D8 is owner-confirmed as required (2026-10-28). Vendor/subscription, budget and privacy/retention rules remain open. No SMS subscription or credential is assumed, and the current booking UI does not claim a phone is verified. Public appointment creation is fail-closed by default until Task 15 supplies the approved verification grant flow.

When the provider and remaining policy decisions are approved, implement this boundary in the backend verification module, not in the public UI:

```php
interface PhoneVerificationProvider
{
    public function sendCode(VerificationChallenge $challenge): ProviderSendResult;
    public function verifyCode(VerificationChallenge $challenge, string $code): VerificationGrant;
}
```

Required setup, in order:
1. Owner records provider name, contract/terms, budget ceiling, approved templates, rate limits, expiry/attempt policy and retention in a decision record.
2. Add provider credentials to the deployment secret store only. Add names (not values) to `.env.example` only after provider approval.
3. Keep vendor-specific HTTP in one adapter using Laravel HTTP client, strict timeout, bounded retry only when send is idempotent, response redaction and no message body/phone/code in logs.
4. Use a fake provider in tests. Never send real SMS from unit, feature, browser or CI tests.
5. Bind the production adapter only when required config is present; keep the `BOOKING_PHONE_VERIFICATION_REQUIRED=true` gate on and fail closed if adapter/config is missing.
6. Bind the verification grant to one normalized phone + one appointment attempt/slot, single-use, short-lived, server-side verified, and consumed atomically by appointment creation. Never trust a browser-only `verified=true` flag.
7. Add CSRF, IP/normalized-phone throttles, abuse controls, user-safe Persian/English errors, resend limits, and provider-failure handling before enabling the UI.
8. Re-run security/privacy review and E2E tests using only synthetic phone data before release.

Task 16 leaves the UI/API payload unchanged until Task 15 implements an approved server capability. No SMS vendor SDK, provider endpoint, credential field, OTP code UI or bypass was added.
