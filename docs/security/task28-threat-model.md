# Task 28 — Threat model update

## Assessment boundary

- Repository: `sepehrsamad1/kouhfar-dental`
- Base: `main` at `ff290289488378ff1b70ebabac66f3fefa0da937`
- Audit branch: `task/28-security-privacy-audit`
- Runtime: Laravel 13, PHP 8.3+, Blade, Vanilla JS, Vite, PostgreSQL, Eloquent, queues, private Laravel Storage.
- Environment assessed: source, migrations, tests, configuration and GitHub Actions. No production system, third-party account, live provider, real patient record, real image or real credential was accessed.
- Target: OWASP ASVS v5.0.0 Level 2 (see the scoped checklist).

## Assets

1. Staff credentials, authenticated sessions and authorization roles.
2. Patient names and normalized mobile numbers.
3. Appointment schedules, service selections, status and history.
4. Private patient/clinical and Smart Smile images.
5. Consent evidence and Smart Smile bearer tokens/results.
6. Editorial drafts and approval history.
7. AI request/usage metadata and approved knowledge content.
8. Audit events, notification state, queue payloads and idempotency snapshots.
9. Application secrets, provider credentials, database and storage configuration.
10. Backups and operational logs (deployment details not supplied).

## Trust boundaries and data flows

- Anonymous browser → public Blade pages and versioned public API routes.
- Anonymous browser → booking API; validation, throttling, idempotency and database concurrency protections are server-side. Phone verification remains fail-closed until provider setup.
- Anonymous browser → Smart Smile consent/upload/status/delete. A 64-character random bearer token is hashed in PostgreSQL; uploaded image is processed and stored on the private media disk.
- Anonymous browser → dental assistant. AI remains feature-flagged; current configured provider is fake. User message is evaluated by safety guard; approved published knowledge is bounded before provider abstraction.
- Staff browser → Laravel web middleware → staff guard/session registry → Policies/Gates → patient, appointment, content, media and AI admin actions.
- Laravel worker → PostgreSQL queue → media/Smart Smile/notification processing.
- GitHub Actions → source checkout, dependency registries, PostgreSQL service, browser tests and build artifacts. No patient data should enter CI fixtures or artifacts.

## Threat actors

- Unauthenticated internet user attempting enumeration, booking abuse, token guessing or upload abuse.
- Authenticated low-privilege staff member attempting horizontal/vertical privilege escalation.
- Malicious or compromised staff account.
- Malicious user-supplied text or file content.
- Compromised dependency, build action or leaked repository secret.
- Misconfigured hosting/storage/logging/backup provider.
- Future AI/SMS provider or compromised integration credentials.

## Abuse cases and current controls

| Abuse case | Existing / added control | Remaining exposure |
|---|---|---|
| Patient/appointment IDOR | Policy/Gate checks; practitioner-scoped list queries; tests for practitioner patient isolation and staff roles | Complete route-by-route object authorization matrix and negative tests remain open. |
| Mass assignment | Form Requests, validated payloads, explicit patient field assignment; new hostile-field regression test | Audit all remaining write paths; CI pending. |
| Open redirect after staff login | Local-path allowlist strengthened to reject schemes, hosts, backslashes and control characters; regression test added | CI pending; verify reverse-proxy/path normalization in deployed environment. |
| CSRF on staff mutations | Laravel web middleware; appointment API route explicitly includes web middleware | Production-browser negative test and proxy/cookie behavior not verified. |
| Session theft/fixation | Session ID regenerated at login; logout revokes registry; HttpOnly/SameSite; Secure default-on outside local/testing | Timeout values unset; production HTTPS and cookie behavior not tested against deployment. |
| Brute force | Per-email+IP login throttling; generic error; failed-attempt hashes | Distributed/global IP throttling and alerting not verified. |
| SQL injection | Eloquent/query builder binding; new SQL-looking search regression test | No external DAST/fuzz run; full raw-query inventory and dynamic query review remain open. |
| Stored XSS | Blade escaping; sanitization allowlist; existing regression test for script markup | Full DOM/reflected payload corpus and browser CSP review remain open. |
| Malicious upload / image bomb | MIME sniffing, decode, byte/pixel/dimension bounds, re-encoding, private storage, signed authorized download | Production storage ACL, malware scanning decision and deployment limits not verified. |
| SSRF | No arbitrary URL fetch or active external AI/SMS provider in current configuration | Reassess before enabling a network provider, webhook, URL preview or user-controlled remote import. |
| Secret exfiltration | Blank `.env.example` placeholders; provider credentials not committed by current configuration | Full-history Gitleaks scan and credential-rotation evidence required. |
| Privacy leakage in AI/logs | AI request recorder stores approved content IDs and aggregate metadata; safe failure logs; audit metadata allowlist | Queue/failed-job payloads, app log sinks, backup data and retention are not fully evidenced. |
| Stale bearer token | Smart Smile token is high entropy and stored as a hash; route throttles apply | No token expiry field/TTL. Owner must approve TTL; do not invent it. |
| Supply-chain compromise | Composer/npm lockfiles and GitHub Actions CI | Add dependency audit and full-history secret scanning; action pinning and production build provenance remain open. |

## Security assumptions to validate before production

- TLS is enforced at the edge and forwarded-proto trust is correctly configured.
- PostgreSQL uses a least-privilege runtime role; migrations use a separate privileged role.
- Private media storage is not web-readable and is backed up/deleted according to approved policy.
- Application debug mode is disabled and secrets are injected from a secret manager or protected deployment environment.
- Queue workers, scheduler, failed-job store and log sinks have restricted access and approved retention.
- Production rate limiting uses a shared backend across instances.
- Staff accounts are individually assigned, promptly disabled when no longer authorized, and protected by an owner-approved authentication policy.
- Any future AI/SMS provider receives only the minimum necessary data and is approved by the clinic before activation.

## Explicit exclusions

No production testing, social engineering, credential guessing against real accounts, third-party scanning, external provider calls, destructive migration, database purge, live patient data, real patient images or secrets were used. These are outside the granted safe repository/CI audit scope.
