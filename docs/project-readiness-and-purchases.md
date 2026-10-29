# Project Readiness & Deferred Purchases

> Purpose: keep a clear record of things intentionally **not activated or purchased yet** so a future AI, teammate, or new chat does not mistake them for missing implementation.

## Current rule

Do not purchase or activate an external paid service merely because the code can support it.

If a provider/service is not required for the current development task, keep the integration behind an abstraction, use a safe development/test driver, document the required activation steps, and continue with tasks that do not depend on the real service.

Only activate a real provider when the owner has the required account/credits/credentials and the task actually needs production delivery.

## Deferred items

### 1. Real OTP / phone verification service — REQUIRED LATER

**Status:** Deferred. Task 15 is intentionally not fully activated yet.

The application should keep OTP delivery provider-backed and replaceable. Do not put provider credentials or real patient phone data in the repository.

When Task 15 is resumed:

- Choose and purchase/activate the approved SMS/OTP provider.
- Add the provider credentials through deployment secrets/environment configuration.
- Enable the real SMS provider only in the appropriate production environment.
- Keep fake/log/null providers for development and automated tests.
- Verify rate limits, expiry, retry behavior, abuse protection, and phone-verification flow before production.
- Run end-to-end verification with a controlled test number before opening the feature to real users.
- Do not hard-code the provider choice into domain logic.

**Current reason it is absent:** real OTP delivery is an external-service activation and was deliberately postponed so development can continue without unnecessary purchase/cost.

### 2. Kavenegar production SMS activation — REQUIRED LATER

**Status:** Integration foundation prepared; real sending intentionally disabled.

Task 22 defines a provider abstraction and a Kavenegar provider, while development/testing can use Log/Null/Fake providers.

Before production SMS is enabled:

- Create/activate the Kavenegar account.
- Purchase/add the required SMS credit/balance or production service.
- Obtain the required API credential(s).
- Store credentials as deployment secrets/environment variables only.
- Configure the production SMS driver.
- Verify the approved appointment/status templates and delivery behavior.
- Test quiet-hours behavior and user-initiated transactional bypass.
- Confirm the production environment is using the intended provider before sending real messages.

**Important:** this is not currently a blocker for normal development. Do not make real SMS calls from CI or local tests.

### 3. Production hosting/deployment infrastructure — REQUIRED BEFORE REAL LAUNCH

**Status:** Not a current development blocker.

Before public production launch, the deployment environment will need, at minimum:

- PHP/runtime compatible with the project.
- PostgreSQL production database.
- HTTPS/TLS and a production domain.
- Secure environment/secrets configuration.
- Private media storage with the required filesystem permissions.
- A persistent Laravel queue worker for database jobs.
- Laravel scheduler execution (cron/scheduler equivalent) for scheduled commands.
- Production logging/error monitoring.
- Backups and a tested restore procedure.
- Appropriate cache/session configuration.
- Production mail/SMS providers if those features are enabled.

Do not purchase hosting/domain infrastructure solely to continue local development.

### 4. Production credentials/secrets — REQUIRED BEFORE LAUNCH

These are configuration items, not things to commit to Git:

- Laravel application key for the production environment.
- Production database credentials.
- SMS/OTP provider credentials.
- Any future AI/API credentials.
- Storage credentials if external/private object storage is selected.
- Other third-party credentials introduced by later approved features.

Never commit real credentials, API keys, provider tokens, or real patient data.

## What is intentionally NOT a purchase requirement yet

The following can continue using local/test implementations unless a later task explicitly requires production activation:

- OTP provider activation.
- Real Kavenegar SMS delivery.
- Production hosting.
- Production domain.
- Production database.
- Production AI/API credentials.
- Production external storage.

## AI / future external services

If a later AI task requires an external paid API, first determine whether the task can be implemented and tested with a local/fake provider.

If real credentials are genuinely required:

1. Document the exact service and why it is required.
2. Record whether it is paid and what must be purchased/activated.
3. Keep the code provider-abstracted.
4. Add test doubles so CI never depends on the paid service.
5. Tell the owner before any purchase or activation is needed.

## Handoff rule for future AIs

When starting work in a new chat or with another AI, read this file before concluding that a provider is "missing."

A missing production credential/provider may be **intentional** rather than unfinished work.

The correct question is:

> Is the current task blocked without the real service, or can we continue with the existing abstraction and test/fake provider?

If it can wait, continue development and leave the item documented here.

## Last reviewed

- Task 21: Secure media system — merged and CI verified.
- Task 22: Queues/scheduler/notifications — provider abstraction prepared; real SMS activation deferred.
- Task 15: OTP/phone verification — intentionally deferred until its requirements and provider activation are ready.
