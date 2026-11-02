# Task 28 — Security findings register

Assessment baseline: `main` commit `ff290289488378ff1b70ebabac66f3fefa0da937`; remediation branch `task/28-security-privacy-audit`.

Severity meanings for this register:
- **Critical:** direct compromise or broad unauthorized disclosure/control with severe impact.
- **High:** material risk to patient/staff data or core security boundary; blocks release.
- **Medium:** meaningful weakness with bounded exploitability/impact; must be fixed or explicitly accepted before production.
- **Low:** defense-in-depth or operational improvement.

## Findings

| ID | Severity | Finding / reproduction | Owner | Fix / retest | Status |
|---|---|---|---|---|---|
| SEC-28-001 | Medium | Staff login accepted a same-origin-looking intended path without explicitly rejecting backslashes/control characters. A path beginning with slash plus backslash can be interpreted differently by URL parsers. Regression input: `/\\evil.example/path`. | Engineering | `StaffAuthController::safeIntendedUrl` now rejects URI scheme/host/user/pass, protocol-relative paths, backslashes and control characters. Regression cases added for backslash, protocol-relative and absolute URLs. | Remediated in branch; CI retest pending |
| SEC-28-002 | Medium | Session cookie Secure behavior depended entirely on `SESSION_SECURE_COOKIE`; `.env.example` had false, so an environment that copied it without overriding could issue cookies without Secure over HTTPS. | Engineering / deployment | `config/session.php` now forces Secure outside local/testing. Added cookie regression test for Secure, HttpOnly and SameSite=Lax when secure mode is active. Production HTTPS/proxy test remains deployment evidence. | Remediated in branch; CI retest pending |
| SEC-28-003 | High | Retention is incomplete for login-attempt hashes, staff session registry, audit logs, consent, Smart Smile session/result metadata, AI operational records, queue/failed-job payloads, notification outbox, idempotency snapshots, deletion requests, application logs and backups. Several tables have no purge job; Smart Smile session rows have no expiry. | Clinic owner for policy; engineering for implementation | Approve a complete data-retention schedule and deletion/backup policy, then implement idempotent purge jobs and tests. Inventory: `task28-data-inventory.md`; PIA: `task28-privacy-impact-assessment.md`. | OPEN — RELEASE BLOCKER |
| SEC-28-004 | Medium | Smart Smile consent token has high entropy and is hashed at rest, but the token/session has no expiry. Anyone possessing a leaked token may continue to query/delete its simulation session while the row exists. | Clinic owner for TTL; engineering for implementation | Owner must approve token TTL and post-expiry status/deletion behavior. Then add `expires_at`, fail-closed resolution and tests. No lifetime has been invented. | OPEN — owner decision |
| SEC-28-005 | High | No completed full-history secret scan or dependency vulnerability scan was evidenced on the base branch. A clean `.env.example` is not proof that earlier commits are free of secrets. | Engineering / repository owner | Add Gitleaks full-history scan and Composer/npm lockfile audits to CI. Review results and rotate/revoke any historical credential if found; deleting a secret from the current tree is not sufficient. | OPEN until CI scans complete |
| SEC-28-006 | Medium | Production environment and infrastructure controls cannot be verified from repository source: TLS/proxy, DB runtime privileges, storage ACLs, backup encryption/retention, log sink access/retention, production debug flag and secret injection. | Deployment owner / clinic owner | Supply deployment configuration/evidence and perform a controlled staging review. No production access was attempted. | OPEN — deployment evidence |
| SEC-28-007 | Medium | Staff idle and absolute session timeout values are intentionally unset; registry session expiry falls back to ten years. This is not an implementation defect if the clinic has not approved timeout policy, but it leaves long-lived sessions possible. | Clinic owner | Approve idle and absolute timeout values; configure and verify revocation/purge. Do not invent values. | OPEN — owner decision |
| SEC-28-008 | Medium | Full route-by-route IDOR/authorization matrix, production-like CSRF negative tests, browser DOM-XSS corpus and rate-limit behavior across multiple app nodes are not evidenced. | Engineering | Extend route matrix and staging tests after retention/policy decisions; no external/production probing without authorization. | OPEN — verification scope |
| SEC-28-009 | Medium | Dormant Kavenegar adapter builds its outbound URL from environment-configured `KAVENEGAR_ENDPOINT` and puts the API key in the URL path. This is not user-controlled from public request input today, but unsafe deployment configuration or redirects could create an SSRF/credential-exposure boundary. | Engineering / deployment owner | Before provider activation, restrict endpoint to the approved HTTPS host/path, disable redirects to unapproved/internal destinations, keep request/exception URLs out of logs, and add mocked HTTP tests. Provider is currently disabled. | OPEN — before SMS activation |
| SEC-28-010 | High | Practitioner policy inherited `view` permission for patient updates and full mobile reveal. This allowed an assigned dentist to modify patient demographics and reveal a direct identifier, exceeding the approved role boundary (dentists view assigned patients and complete appointments; patient administration remains admin/reception). | Engineering | `PatientPolicy::update` and `revealMobile` now allow only admin/reception; patient list hides the reveal control from practitioners while retaining masked mobile. Added endpoint/UI regression test for an assigned practitioner. | Remediated in branch; CI retest pending |

## Closed / remediated findings

No finding is considered closed until CI runs on the branch and the regression tests pass. SEC-28-001 and SEC-28-002 are code-remediated but remain pending CI verification.

## Release decision

**BLOCK RELEASE.** SEC-28-003 and SEC-28-005 remain high and open pending owner approval/automated scan evidence. Medium findings SEC-28-004, SEC-28-006, SEC-28-007 and SEC-28-008 also remain open. No critical finding was confirmed from the reviewed source, but this is not equivalent to a complete penetration test.

Do not test production or third-party systems without written authorization. Do not use real patient data or credentials in CI.
