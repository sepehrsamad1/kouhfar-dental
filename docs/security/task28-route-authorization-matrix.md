# Task 28 — Route-level authorization and abuse-test matrix

**Scope:** source review and existing automated tests on branch `task/28-security-privacy-audit`. This is not a claim that every route has an individual negative HTTP test. Rows marked PARTIAL or OPEN remain outstanding under SEC-28-008.

## Global middleware boundary

All routes declared in `routes/web.php` are subject to Laravel's `web` middleware stack. The staff route group additionally applies `auth:staff`, `staff.session.fresh`, and `PreventStoreCache`. Staff authorization is enforced in controllers through Policies/Gates; hiding a UI action is not treated as authorization.

The public booking POST in `routes/api.php` explicitly adds `web` middleware and `throttle:30,1`. Public services and availability are read-only and throttled. Smart Smile endpoints are declared in `routes/web.php`, so they inherit the web middleware stack and also have route-specific throttles.

## Staff object routes

| Route / object | Server-side control reviewed | Existing regression evidence | Assessment |
|---|---|---|---|
| `PATCH /staff/patients/{patient}` | `PatientPolicy::update`; admin/reception only | `PatientManagementTest`: practitioner edit denied; hostile mass-assignment rejected; optimistic version conflict does not expose patient data | PARTIAL — route-level role cases exist; full admin/reception/unknown-role matrix remains |
| `POST /staff/patients/{patient}/archive` | `PatientPolicy::archive`; admin only | `StaffAuthorizationTest` checks patient archive policy; patient workflow tests cover archive/audit behavior | PARTIAL — direct endpoint matrix incomplete |
| `GET /staff/patients/{patient}/mobile` | `PatientPolicy::revealMobile`; admin/reception only | `PatientManagementTest` denies assigned practitioner and verifies masked list output | PARTIAL — other-role endpoint cases not exhaustive |
| `POST /staff/appointments/{appointment}/confirm` | `AppointmentPolicy::update`; admin/reception | `Task19StaffAppointmentCalendarTest`: role separation, stale version, availability recheck | PARTIAL |
| `POST /staff/appointments/{appointment}/cancel` | `AppointmentPolicy::cancel`; admin/reception | `Task19StaffAppointmentCalendarTest`: role separation and 24-hour cutoff | PARTIAL |
| `POST /staff/appointments/{appointment}/complete` | `AppointmentPolicy::complete`; admin/reception or assigned practitioner | `Task19StaffAppointmentCalendarTest`: role separation and legal state transitions | PARTIAL |
| `POST /staff/appointments/{appointment}/no-show` | `AppointmentPolicy::complete`; admin/reception or assigned practitioner | Calendar tests cover started confirmed appointment and role separation | PARTIAL |
| `PATCH /staff/appointments/{appointment}/reschedule` | `AppointmentPolicy::update`; admin/reception | Calendar tests cover role separation, stale version, cutoff and occupied-slot conflict | PARTIAL |
| `GET /staff/media/{mediaAsset}/download` | Signed URL middleware plus `MediaAssetPolicy::view`; admin only; private disk; deleted assets return 404 | `MediaAssetSecurityTest`: unsigned/expired URL denied; non-admin denied; private storage and no-store response | PARTIAL — signed URL and policy tested; exhaustive cross-object owner matrix remains |
| `DELETE /staff/media/{mediaAsset}` | `MediaAssetPolicy::delete`; admin only | `MediaAssetSecurityTest`: role denial and retention-aware soft-delete/purge tests | PARTIAL |
| `PATCH /staff/content/{contentItem}` | `ContentItemPolicy::update`; admin and editable state only | `Task20ContentWorkflowTest`: admin-only actions and published-version immutability | PARTIAL |
| `POST /staff/content/{contentItem}/review` | `ContentItemPolicy::submitReview`; admin and draft only | `Task20ContentWorkflowTest`: reception denied; workflow transitions tested | PARTIAL |
| `POST /staff/content/{contentItem}/approve` | `ContentItemPolicy::approve`; admin and review state only | `Task20ContentWorkflowTest`: explicit approval and audit events | PARTIAL |
| `POST /staff/content/{contentItem}/publish` | `ContentItemPolicy::publish`; admin, review state and required owner approval | `Task20ContentWorkflowTest`: publish without approval denied; approved content published | PARTIAL |
| `POST /staff/content/{contentItem}/archive` | `ContentItemPolicy::archive`; admin and published state only | Content workflow tests cover versioning and archive behavior | PARTIAL |
| `POST /staff/content/{contentItem}/version` | `ContentItemPolicy::createVersion`; admin and published state only | `Task20ContentWorkflowTest`: immutable published versions and new version creation | PARTIAL |
| `PATCH /staff/configuration/services/{service}` | `ServicePolicy::update`; admin only | `ClinicConfigurationTest`: reception denied, admin allowed, stale version rejected, confirmed appointment impact blocked | PARTIAL |
| `PATCH /staff/configuration/practitioners/{practitioner}` and `/services` | `PractitionerPolicy::update`; admin only | `ClinicConfigurationTest`: practitioner-service assignment and version/appointment constraints | PARTIAL |
| `PATCH /staff/configuration/resources/{resource}` | `ResourcePolicy::update`; admin only | `ClinicConfigurationTest`: direct object-route attempts by reception and practitioner are forbidden and leave the resource unchanged; admin update succeeds. | PARTIAL — specific resource endpoint regression added; other dynamic-object route matrices remain incomplete |
| `PATCH/DELETE /staff/configuration/{clinic-hours,working-hours,breaks,exceptions}/{id}` | Corresponding Policies; admin only | `ClinicConfigurationTest`: clinic-hour update/delete and schedule overlap checks; Task 18 covers forms | PARTIAL — direct cross-object role tests not exhaustive |
| `PATCH /staff/ai` | `manage-configuration` Gate; admin only | `Task27AIAdminDashboardTest` covers dashboard controls and validation | PARTIAL |

## Public endpoints and bearer-token objects

| Route | Control reviewed | Existing regression evidence | Assessment |
|---|---|---|---|
| `POST /api/v1/appointments` | Form Request validation, web/CSRF middleware, throttle, idempotency and concurrency-safe creator; phone-verification gate fails closed | `AppointmentCreationTest`, `AppointmentConcurrencyTest`, `PublicBookingUiApiTest` | PARTIAL — production-browser CSRF and distributed throttle tests remain open |
| `GET /api/v1/availability/{service}` and `GET /booking/availability/{service}` | Public-bookable service filtering and throttling | `AvailabilityEndpointTest`, browser booking tests | PARTIAL |
| `POST /api/v1/smart-smile/consent` | Feature/release gate, route throttle, random bearer token, hashed token at rest | `Task26SmartSmileTest`: disabled gate and consent flow | PARTIAL — consent evidence retention/TTL not approved |
| `POST /api/v1/smart-smile/upload` | Form Request token shape, MIME/size checks, consent resolution, private image processing, one upload per consent | Smart Smile and media tests cover consent-required and invalid uploads | PARTIAL |
| `POST /api/v1/smart-smile/status` | Random bearer token lookup; response excludes result unless completed and is non-diagnostic | Smart Smile tests verify invalid/revoked token access | PARTIAL — bearer token has no approved expiry (SEC-28-004) |
| `DELETE /api/v1/smart-smile` | Bearer token required; soft-deletes media and revokes session | Smart Smile tests verify deletion and revoked-token behavior | PARTIAL — expiry and post-expiry behavior require owner decision |

## Cross-cutting controls

| Control | Evidence | Assessment |
|---|---|---|
| CSRF | Web middleware for staff mutations and explicit web middleware for public booking POST; no production-browser negative test recorded | PARTIAL |
| SQL injection | Eloquent/query-builder bindings in inspected paths; SQL-looking patient-search regression test | PARTIAL — raw-query inventory and DAST/fuzz not complete |
| Stored XSS | Blade escaped rendering; content workflow script-markup regression test | PARTIAL — no full DOM/reflected payload corpus |
| Open redirect | Intended URL parser rejects external schemes/hosts, protocol-relative paths, backslashes and control chars; regression test passed in CI #862 | PASS for reviewed staff-login redirect path |
| Session lifecycle | Rotation, logout revocation, deactivated-account revocation, optional idle/absolute timeout tests; production/staging timeout values not approved | PARTIAL |
| Rate limiting | Route throttles and per-email+IP login throttling; shared distributed backend behavior not verified | PARTIAL |
| Upload abuse | MIME sniffing, decode/re-encode, size/pixel bounds, private storage and signed downloads; automated tests passed | PASS for tested application-level image controls |
| SSRF | No user-controlled URL fetch found; dormant Kavenegar adapter has fixed HTTPS allowlist, redirect disabled, synthetic negative tests passed in CI #862 | PARTIAL — provider credential URL-path residual risk |
| Secret scanning | Full-history Gitleaks passed in CI #862; GitHub secret alerts and non-text artifact/log review not accessible through repository CI evidence | PARTIAL |

## Explicitly unverified

- A full HTTP-level negative test for every dynamic object route and every staff role.
- Browser-enforced CSRF rejection outside the Laravel test harness.
- DOM-XSS payload corpus and response security-header/CSP review.
- Rate-limit behavior across multiple application nodes.
- Production TLS/proxy, database runtime privileges, private storage ACLs, secret injection, backups and log-sink controls.
- GitHub secret-alert dashboard and third-party/provider-side log review.

These are not silently counted as passed. They remain in SEC-28-006 / SEC-28-008 and the ASVS requirement register. No production or third-party testing was performed.
