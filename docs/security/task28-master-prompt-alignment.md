# Task 28 — Master prompt alignment and execution register

Source: `Kouhfar_Data_Security_CRM_Master_Prompt_FA.docx`, version 1.0, dated 2026-11-03.
Branch: `task/28-security-privacy-audit`.

## Interpretation and authority

The source document labels itself a draft policy for review by the clinic owner and legal adviser. Its retention periods and recovery objectives are proposed engineering baselines, not universal legal requirements. This register separates (a) controls already implemented and evidenced in CI, (b) proposed defaults that still require the approval explicitly required by the source, and (c) deployment evidence that cannot be established from repository code.

The user's instruction to continue the Task 28 workflow without repeated chat confirmations authorizes continued engineering and documentation within this task branch. It does not constitute a clinic-owner signature, legal review, production access authorization, or permission to merge this branch.

## Policy-by-policy alignment

| Master prompt item | Proposed policy / requirement | Current branch evidence | Execution state |
|---|---|---|---|
| 1. Smart Smile images and data | Raw input images: 90 days after processing. Final output: 12 months unless separately incorporated into a clinical record. | Existing media purge policy distinguishes pre-consultation media (90 days) and clinical media (2 years). User selected 12 months for final Smart Smile output in this conversation. | 12-month output recorded as user-selected engineering baseline, not clinic-owner approval. Verify result/media classification and automated purge path before implementation. |
| 2. Consent evidence | Preserve consent text version, timestamp, purpose, patient/request reference and consent status. Proposed 6 years after last related processing. Withdrawal stops future processing immediately. | Consent records exist; exact retention and token lifecycle remain open in the owner decision register. | BLOCKED / OWNER AND LEGAL REVIEW REQUIRED for the 6-year period. Implement withdrawal semantics only after verifying current consent state transitions and dependencies. |
| 3. Smart Smile access token | Access token proposed TTL 15 minutes; optional refresh token max 30 days with rotation and revocation. No raw token in DB, URL, logs or errors. | Token is high-entropy and hashed at rest; expiry field/TTL is absent. | BLOCKED / OWNER DECISION REQUIRED for TTL and status/revocation behavior. No token lifetime is silently introduced. |
| 4. Successful jobs | Keep completed job details 7 days; retain only necessary anonymous statistics after that. | Queue/job retention not yet evidenced as configured. | PROPOSED 7-day default; inspect queue driver/schema and payload sensitivity before adding cleanup. |
| 5. Failed jobs | Keep failed-job details 30 days after failure; bounded retries and idempotent effects. | Failed-job retention not yet evidenced as configured. | PROPOSED 30-day default; implementation requires safe handling of serialized payloads and operational review. |
| 6. Application and security logs | General operational logs 30 days; security/audit logs baseline 12 months. Exclude direct identifiers, images, secrets and medical text. | Local application logs: 30 days. Original audit rows: indefinite by prior explicit owner decision; audit backups: at least 90 days. | Preserve the stronger existing audit-log owner decision. Do not shorten original audit rows to 12 months. Security-event log categories need a precise inventory and owner/legal reconciliation. |
| 7. Sent notifications | Metadata (type, channel, time, status, booking reference) 12 months; message body only if necessary and minimized. | Notification outbox retention not yet evidenced. | PROPOSED 12 months for metadata; inspect schema and separate message body from metadata before implementation. |
| 8. Failed notifications | Error and attempt details 30 days after last attempt; bounded retry/backoff and duplicate prevention. | Notification error retention not yet evidenced. | PROPOSED 30 days; implementation requires retry and idempotency tests. |
| 9. Deletion/correction request handling | Register and assess promptly; 30 days is an operational target, not a universal legal guarantee. | Deletion-request retention and end-to-end deletion propagation are not complete. | Treat 30 days as target SLA only, subject to applicable law and exceptions. No automatic deletion of all patient data follows from this target. |
| 10. Deletion-request audit | Minimal request ID, date, data category, outcome, exception reason and executor; proposed 3 years subject to legal approval. | Deletion request lifecycle/retention remains open. | BLOCKED / LEGAL REVIEW REQUIRED for 3-year retention. Do not copy deleted personal data into the audit record. |
| 11. Staff accounts | Revoke sessions/tokens/access immediately on termination or role change. 90 days applies only to unnecessary operational profile data, not required HR or audit records. | Staff deactivation immediately revokes active sessions. Profile retention is unresolved. | Session revocation implemented and CI-tested. 90-day operational profile cleanup remains proposed and requires data classification. |
| 12. Patient-match flags | Keep unresolved flags until reviewed; remove resolved work-queue flag after 90 days; minimal decision/audit outcome 12 months. Never auto-merge/delete/update based only on a flag. | Patient matching uses review flags; retention policy is unresolved. | PROPOSED 90-day/12-month lifecycle. Verify that the flag is non-destructive and preserve human review. |
| 13. MFA | Require staff MFA; encrypted TOTP secrets; one-time recovery codes stored as secure hashes; raw codes shown once. | TOTP MFA and one-time recovery codes implemented; CI-tested. | IMPLEMENTED / CI-VERIFIED. |
| 14. MFA recovery | Recovery code or independent factor first; otherwise re-identity-proof through an authorized person, revoke old MFA/sessions, enroll new factor, issue new codes, audit all steps. | Enrollment and recovery-code use are audited; no complete lost-device identity-proofing workflow is evidenced. | PARTIAL. Operational identity-proofing and authorized reset procedure remain a rollout blocker; do not invent the clinic's identity proofing standard. |
| 15. Daily backup | At least daily encrypted database backup outside primary server; proposed rolling retention 35 days; consider PITR/WAL. | Daily encrypted audit-log backup exists; full database/files backup and separate destination are not evidenced. | PARTIAL. 35-day general backup policy is a proposed baseline; full database/files backup needs infrastructure design and deployment evidence. |
| 16. Long-term backup | Monthly copies up to 12 months only if justified; 3-2-1 pattern; separate/offline/immutable copy and separately held encryption key. | Audit-log backups have 90-day minimum retention; separate physical storage and key/ACL provisioning remain unverified. | PARTIAL. Long-term copy policy requires business/legal justification and storage evidence. |
| 17. Restore testing | Full isolated restore at least quarterly and after significant infrastructure/backup changes; verify data, files, permissions and core workflows. | Monthly authenticated temporary-table audit-log restore verification exists; this is not a full isolated system recovery. | PARTIAL. Full quarterly isolated restore remains a deployment exercise, not proven by unit tests. |
| 18. RPO/RTO | Proposed RPO <=24 hours and RTO <=8 hours; prove through recovery tests and align with cost/clinic needs. | No production infrastructure or full recovery evidence supplied. | BLOCKED / OWNER AND INFRASTRUCTURE APPROVAL REQUIRED. Targets remain proposed, not achieved service levels. |
| 19. CRM and appointment history | Retain CRM and appointment history without automatic expiry; corrections are append/history-aware; separate clinical record, Smart Smile, tokens and technical logs. | Existing owner decision register records indefinite patient/CRM and original audit retention; role-based access and audit controls exist. | Existing owner-approved policy remains in force; legal rights/valid deletion requests and applicable law are not waived. |

## Cross-cutting technical requirements

- Keep secrets, tokens, OTP values, patient data and clinical content out of source control, URLs, logs, error messages and CI artifacts.
- Apply deletion/expiry consistently across database rows, private file storage, queues, caches, notification outbox and third-party systems; document how restored backups reapply later deletion restrictions.
- Keep retries bounded and side effects idempotent.
- Enforce authorization and validation server-side.
- Keep live AI/SMS providers disabled until data flow, provider terms, credential handling and required approval are verified.
- Use synthetic test data only.

## Current execution boundary

Latest fully successful CI verification remains run #979 on revision `3282ab38d55df8d3b5cd7cb01a70ed6aada4821c`. The newer run #1006 passed Gitleaks, dependency audits and JavaScript lint/typecheck/build but failed Pint on one `fully_qualified_strict_types` finding in `DeletionRequestWorkflowTest.php`; PHPStan, migrations, Laravel tests and browser tests were skipped. A formatter correction is committed as `994288e4456d2f100148d3685f883646c8028463` and awaits a new CI run. Run #979 includes the completed-job history migration/model/listener and its model contract test. Earlier counts and finding-specific evidence remain historical run #937 evidence.

Still not proven: full route-by-route IDOR/CSRF/DOM-XSS/distributed rate-limit verification; full database/files backup and isolated restore; physically separate backup destination, key/ACL and monitoring; production TLS/proxy/database/storage/secrets/logging controls; provider/network URL-path credential redaction; and operational MFA identity-proofing.

**Release remains blocked.** This register is an engineering alignment document, not legal advice, owner signature, production approval, or ASVS certification.
