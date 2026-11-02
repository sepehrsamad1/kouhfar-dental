# Task 28 — OWASP ASVS v5.0.0 Level 2 checklist

Assessment baseline: repository branch `task/28-security-privacy-audit`, based on main commit `ff290289488378ff1b70ebabac66f3fefa0da937`.

## Target and scope

**Selected target: OWASP Application Security Verification Standard (ASVS) v5.0.0, Level 2.**

Rationale: the application has staff authentication, patient names and mobile numbers, appointment history, private dental images, consent records and staff audit trails. A baseline-only review would be insufficient for an application that is intended to process sensitive personal and clinical-adjacent data.

This is a scoped engineering assessment, not an OWASP certification or a claim that every ASVS requirement has passed. ASVS 5.0.0 is the stable release published on 30 May 2025. Requirement identifiers must be version-qualified because identifiers can change between releases: `v5.0.0-...`.

Evidence labels:
- **PASS (code/test):** source and/or an automated test directly supports the control.
- **PARTIAL:** some evidence exists, but the whole control or deployment property is not proven.
- **OPEN:** evidence is missing, a decision is unapproved, or a retest is pending.
- **N/A (scoped):** no current application feature exists to exercise the control; reconsider if the architecture changes.

## Task 28 control checklist

| Control area | Assessment / evidence | Status |
|---|---|---|
| Object-level authorization / IDOR | Staff routes are behind `auth:staff`; patient and appointment actions call Gates/Policies. Practitioner queries are scoped to assigned patients/appointments. Task 28 further restricts patient edits and full-mobile reveal to admin/reception (practitioners retain masked assigned-patient view and own-appointment completion); endpoint/UI regression test added. Existing `PatientManagementTest`, `StaffAuthorizationTest`, and media security tests cover denied access. Full route-by-route negative test matrix is not yet complete. | PARTIAL |
| Mass assignment | Patient create/update use explicit field assignment and Form Requests. Content writes use validated fields. Added hostile-field regression test for patient creation in `PatientManagementTest`. All model/controller write paths still require exhaustive review. | PARTIAL |
| Open redirects | Staff login redirect accepted only a single-slash local path; ambiguous backslash and control-character paths are now rejected. Regression cases added to `StaffAuthenticationTest`. CI retest pending. | PARTIAL |
| CSRF | State-changing staff routes are in Laravel's `web` route group. Public appointment creation explicitly adds `web` middleware. No production-browser negative CSRF test has been recorded; Laravel's test environment normally suppresses CSRF enforcement. | PARTIAL |
| Session cookie flags | `HttpOnly=true`, `SameSite=Lax`; Secure is now default-on outside local/testing, while remaining configurable for local/testing. Cookie flag regression test added. Production HTTPS/proxy configuration is not available for verification. | PARTIAL |
| Session lifecycle | Session ID rotates after login; logout revokes registry session, invalidates session and regenerates CSRF token; session freshness middleware checks revocation and optional owner-configured timeouts. Owner has not set idle/absolute timeout values; registry expiry defaults to ten years when unset. | OPEN |
| Rate limits | Staff login has per-email+IP throttling; public assistant, Smart Smile and booking endpoints have route throttles. No evidence yet of a separate global staff-login IP ceiling or distributed rate-limit behavior under multiple application nodes. | PARTIAL |
| SQL injection | Application search paths inspected use Eloquent/query builder bindings; migration SQL found in inspected files is static schema/data transformation SQL. Patient search SQL-looking input regression test added. Full automated DAST/fuzzing not performed. | PARTIAL |
| Stored/reflected XSS | Public content view uses Blade escaped output; `Task20ContentWorkflowTest` explicitly asserts script markup is escaped. Sanitization policy documents `textContent` and allow-listed trusted templates. No full browser payload corpus/DOM-XSS scan recorded. | PARTIAL |
| SSRF | No user-controlled URL fetch feature is present. The dormant Kavenegar adapter uses an environment-configured endpoint (not request input) with Laravel HTTP client; treat it as a configuration trust boundary and validate the HTTPS host/redirect policy before activation. Reassess before enabling any network provider or user-configurable URL. | N/A (current runtime) |
| File upload abuse | Media service checks byte size, detected MIME, image decoding, dimensions/pixel count, re-encodes JPEG/PNG/WebP and stores UUID paths on private disk. Existing tests cover invalid payloads, size, EXIF removal, private storage, signed download and admin authorization. | PASS (application controls) |
| Secrets | `.env.example` contains blank provider-key placeholders; no real provider is configured. Gitleaks full-history scan and GitHub secret-alert review must pass before closure. Never treat a clean working-tree scan as proof that old commits contain no exposed credential. | OPEN |
| Dependency vulnerabilities | Lockfiles exist. Composer and npm audit are being added to CI; results have not yet been returned for this branch. | OPEN |
| Error/log privacy | AI provider catches exceptions and emits only feature/capability metadata; AI request rows store content IDs and usage metadata, not prompt text. AuditLogger allow-lists metadata keys and excludes patient names/mobile. Login attempt rows store hashes. Queue/failed-job payloads, framework logs and retention periods still require review/approval. | PARTIAL |
| Data inventory and retention | Inventory is in `task28-data-inventory.md`. Media retention is documented as owner-approved in Task 21. Retention for authentication, audit, queue, failed-job, notification, idempotency and Smart Smile session metadata is not recorded as approved. | OPEN — release blocker |
| Third-party data flows | No live AI provider or SMS provider is active in the current configuration. If Kavenegar is enabled, it receives patient mobile and SMS body; confirmed/rescheduled messages include appointment date/time, practitioner display name, clinic address and map URL. GitHub Actions/package registries receive source/build metadata. | PARTIAL |
| Transport / production configuration | No production environment, TLS termination, proxy, DB role, storage IAM, backup, log sink or deployment configuration was supplied for inspection. | OPEN |
| Security headers / CSP | No complete response-header/CSP verification was recorded. A strict CSP could break existing inline UI assets and must be introduced only with a compatibility plan and browser regression evidence. | OPEN |

## Required retest commands

Run in CI or a controlled local development environment:

```bash
composer audit --locked
npm audit --audit-level=high
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse
npm run lint
npm run typecheck
npm run build
npm run test:browser
```

Gitleaks must scan the full Git history (`fetch-depth: 0`) in CI. Do not test production or external providers without separate written authorization.

## Closure rule

Task 28 cannot be called complete while any critical/high finding is open, while required retention decisions remain unapproved, or while any scoped control lacks evidence or an explicit open status. This checklist does not represent certification.
