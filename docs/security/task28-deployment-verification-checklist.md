# Task 28 — Production deployment verification checklist

**Purpose:** record checks that cannot be proven from repository source or CI. This is a pre-production checklist, not evidence that the production environment is configured. Do not mark a row complete without attaching deployment evidence and an authorized reviewer.

## 1. Transport, proxy and session security

- [ ] Serve the entire application over HTTPS with a valid certificate chain; redirect HTTP to HTTPS at the edge.
- [ ] Verify Laravel's trusted-proxy configuration contains only the actual load balancer/reverse-proxy addresses. Do not trust arbitrary forwarded headers.
- [ ] Verify the application observes the original client IP only through trusted proxy headers. This affects IP-based OTP abuse controls.
- [ ] Confirm production session cookies are Secure, HttpOnly, SameSite=Lax and use the expected secure prefix.
- [ ] Verify HTTPS termination, forwarded scheme/host handling, HSTS policy and certificate renewal monitoring.
- [ ] Run authenticated and unauthenticated CSRF negative tests in a production-like browser environment; confirm state-changing web routes reject missing/invalid tokens.

## 2. Database and application privileges

- [ ] Use a dedicated PostgreSQL runtime role with only the table/sequence privileges required by the running application.
- [ ] Use a separate migration/deployment role for schema changes; do not give the web runtime role ownership or DDL privileges.
- [ ] Verify TLS is used for database connections where supported by the deployment topology.
- [ ] Confirm PostgreSQL advisory locks are supported and functional for the booking OTP phone/IP quotas and booking capacity locks.
- [ ] Verify backups, migrations and restore tests use separately authorized credentials; never place database passwords in repository files or logs.

## 3. Private storage and backups

- [ ] Confirm patient media, Smart Smile uploads, audit backups and other private files are outside the public web root or served only through authorized application endpoints.
- [ ] Verify filesystem/object-storage ACLs deny anonymous access and restrict read/write/delete to the intended application and administrator roles.
- [ ] Provision the audit backup destination on a genuinely separate physical storage volume or storage service. A different folder or path on the same disk is not sufficient.
- [ ] Inject the 32-byte audit backup encryption key through the deployment secret manager; store key material separately from backup objects and restrict key access.
- [ ] Verify backup job success/failure monitoring, alert routing and scheduler/host-cron execution.
- [ ] Perform and document the monthly authenticated restore/integrity test, then schedule a full isolated database recovery exercise before production acceptance.

## 4. Secrets, logs and provider integrations

- [ ] Inject APP_KEY, database credentials, provider tokens and other secrets through the approved deployment secret manager. Never commit .env, credentials, tokens or encryption keys.
- [ ] Confirm debug mode is disabled and application errors returned to clients are generic.
- [ ] Review application, web-server, reverse-proxy, WAF, observability and provider logs for OTP values, raw patient phone numbers, request bodies, exception details and credentials.
- [ ] MeliPayamak and Kavenegar credentials are embedded in URL paths by their current provider contracts. Keep both live adapters disabled until provider-side and network-side URL-path access-log redaction/retention has been reviewed and the clinic accepts the residual risk or selects header-auth alternatives.
- [ ] Keep SMS_OTP_DRIVER=null until the clinic explicitly approves live SMS activation and the deployment secret is injected. Do not use real patient numbers for staging verification.
- [ ] Keep AI providers disabled until provider/model, data flow, cost, safety evaluation and secret injection are approved.

## 5. Rate limiting and abuse controls

- [ ] Confirm the OTP database-backed limits operate against the production PostgreSQL primary: 5-minute TTL, 5 failed attempts, 60-second resend cooldown, 3 sends/phone/15 minutes, 5 sends/phone/hour and 10 sends/IP/hour.
- [ ] Confirm the trusted-proxy/IP configuration gives the OTP service the real client IP and not a shared untrusted forwarded value.
- [ ] Configure a shared atomic cache/rate-limit store (for example, Redis) for general Laravel route throttles if more than one application node is deployed. The OTP issuance quotas use PostgreSQL advisory locks; this does not automatically make every Laravel throttle distributed.
- [ ] Run authorized staging abuse tests across two application nodes and verify the shared route-throttle state.
- [ ] Monitor rate-limit responses and provider delivery failures without recording OTP codes, raw phone numbers or token-bearing URLs.

## 6. Operational owner decisions still required

- [ ] Approve retention duration for OTP challenge metadata (OD-28-18). No automatic purge is currently enabled.
- [ ] Approve Smart Smile consent-token TTL and post-expiry/revocation behavior (SEC-28-004).
- [ ] Approve retention for consent evidence, queue/failed-job payloads, notification outbox/error metadata and deletion requests (SEC-28-003).
- [ ] Define the controlled, audited lost-authenticator/recovery-code reset and staff identity-proofing process.
- [ ] Decide staff/practitioner profile retention and patient-match flag policy (OD-28-10).
- [ ] Confirm formal clinic approval for enabling live MeliPayamak SMS and review residual URL-path credential exposure (SEC-28-015).

## Evidence record

For each completed item, record: environment, date/time, responsible operator, reviewer, exact command or test, result, and a sanitized evidence reference. Do not attach real patient records, OTP values, credentials, provider URLs containing tokens, database dumps or unredacted logs.
