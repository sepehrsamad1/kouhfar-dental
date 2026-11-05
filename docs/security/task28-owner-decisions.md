# Task 28 — Clinic-owner decisions for security and privacy closure

**Status: OWNER DECISIONS UPDATED — original decisions recorded on 2026-11-03; booking OTP limits additionally approved on 2026-11-04. This is an engineering decision record, not legal advice or production approval.**

## Approved decisions

| ID | Area | Owner decision | Engineering consequence / boundary |
|---|---|---|---|
| OD-28-01 | Patient and clinical data retention | Retain patient data indefinitely for CRM and future clinic use. | Do not automatically delete or anonymize patient, appointment, or related CRM records. This does not override applicable law, a valid legal hold, or a later written owner instruction. No destructive purge job is authorized by this decision. |
| OD-28-02 | Staff session lifetime | 15-minute idle timeout and 8-hour absolute timeout. | Configure both values server-side. A staff session must be revoked when either limit is exceeded. |
| OD-28-03 | Login-attempt hashes | Retain hashed login-attempt metadata for 90 days. | Automatically delete login-attempt rows older than 90 days. Keep only hashed identifiers and IP metadata; never store raw passwords or OTP values. |
| OD-28-05 (partial) | Consent and Smart Smile | Retention duration remains undecided until clinic needs are confirmed. | Do not invent or implement a new expiry/purge period for consent evidence, consent tokens, or Smart Smile session/result metadata. |
| OD-28-06 (partial) | Idempotency records | Delete records after the already-designed 24-hour expiry. | Implement scheduled deletion where `expires_at` has passed. This does not decide retention for queue/failed-job payloads, notification outbox/error metadata, or deletion requests. |
| OD-28-07 | AI operational metadata | Retain AI request/usage operational records for 12 months. | Automatically delete detailed AI request and daily usage rows older than 12 months. Do not retain prompts or raw patient content through this policy. |
| OD-28-04 | Audit-log retention | Retain original audit logs indefinitely. | Do not purge original audit-log rows automatically. Audit logs remain access-controlled and must not contain patient names, mobile numbers, clinical notes, secrets, or raw request bodies. |
| OD-28-08 | Application logs | Store logs locally and retain them for 30 days. | Use local daily log rotation with a 30-day retention window. Do not add an external SIEM/logging provider. Production log access and ACLs still require deployment verification. |
| OD-28-09 | Audit-log backup | Back up audit logs daily; keep backup copies for at least 90 days; store them separately from the primary application/database storage; encrypt them; restrict access to authorized administrators; and perform a restore/integrity test monthly. | The 90-day period applies only to backup copies. It does not authorize deletion of original audit logs. The separate storage location and encryption key must be supplied through deployment secrets/configuration, never committed. Verify that the configured destination is a genuinely separate storage volume/service, not merely another folder on the same disk. |
| OD-28-11 | SMS/OTP capability | MeliPayamak has been purchased for backend OTP delivery. | The provider is MeliPayamak and the user supplied its backend OTP endpoint contract. Store its endpoint token only in deployment secrets. Keep live sending disabled until the adapter and OTP challenge flow are tested in a controlled environment and explicitly enabled. The supplied provider-generated code must be kept server-side for verification and never returned to the browser or written to logs. |
| OD-28-12 | AI architecture | AI models will be added. The architecture must remain provider-independent. The API key will be supplied later through a secure channel. | Keep provider adapters/configuration abstracted and credentials server-side. Do not commit real API keys. AI remains disabled until the chosen provider/model, pricing, safety evaluation and deployment configuration are verified and explicitly activated. |
| OD-28-13 | Session cookies | Secure and HttpOnly must be enabled. SameSite=Lax is the default unless compatibility tests demonstrate that Strict works without breaking required flows. | Keep Secure enforced outside local/testing, HttpOnly enabled, and SameSite=Lax for now. Do not switch to Strict without regression evidence. |
| OD-28-14 | Rate limiting | Use endpoint-specific limits: strict for staff login and OTP, flexible for public read-only pages, and appropriate limits for booking. Apply IP/account-based throttling and anti-abuse controls where appropriate. | Keep limits server-side and test them. OTP limits must prevent SMS abuse and unexpected cost. Distributed multi-node enforcement remains a deployment verification item. |
| OD-28-17 | Booking OTP policy | 5-minute challenge lifetime; 5 failed attempts maximum; 60-second resend cooldown; 3 sends per phone per 15 minutes; 5 sends per phone per hour; 10 sends per IP per hour. | Enforce in the database-backed challenge lifecycle. New issuance invalidates earlier active challenges; verification is one-time and consumed atomically with appointment creation. Live provider activation remains a separate approval. |
| OD-28-16 | Staff MFA | Require TOTP-based MFA for all staff accounts, with one-time recovery codes. | Implement TOTP enrollment/verification for staff and issue one-time recovery codes whose hashes are stored server-side. SMS is not the staff MFA factor. Recovery-code reissue and lost-authenticator reset must be restricted to an audited administrator process; the exact help-desk identity-proofing process remains an operational control. |

## Decisions still open

| ID | Area | Still required |
|---|---|---|
| OD-28-05 (partial) | Consent and Smart Smile | Retention duration for consent evidence and Smart Smile session/result metadata remains undecided. Consent-token TTL and post-expiry/revocation behavior also require a separate decision. |
| OD-28-06 (partial) | Queue and operational records | Retention for queue/failed-job payloads, notification outbox/error metadata and deletion requests remains undecided. The 24-hour idempotency cleanup is approved. |
| OD-28-09 (deployment) | Backup implementation | The policy is approved, but the actual separate storage destination, encryption key provisioning, backup execution evidence, monitoring and restore-test evidence are not yet present in the repository. |
| OD-28-10 | Staff/practitioner profiles and patient-match flags | Account/profile retention after deactivation and whether patient-match flags are required; if retained, approve access roles and a controlled reason-code vocabulary. |
| OD-28-18 | OTP challenge metadata retention | Approve retention duration for challenge records containing keyed phone/IP digests, status and timestamps. | No automatic challenge-row purge is enabled until the owner approves retention. Code digests are cleared on successful verification and terminal invalidation/expiry/exhaustion. |
| OD-28-15 | Kavenegar URL-path credential exposure | Before activating the existing Kavenegar adapter, approve the residual risk and confirm provider/network log redaction, or select a provider that supports header-based authentication. |

## Existing approved media policy remains unchanged

- Unconverted Smart Smile / pre-consultation uploads: 90 days from upload.
- Patient / clinical media: 2 years, anchored to the later of media creation and the patient's last historical appointment.
- Authorized early soft-delete; hard purge after the applicable retention window or 30 days after soft-delete.

This media policy is separate from the owner's decision to retain patient/CRM records indefinitely. Retaining patient records does not mean retaining every media object indefinitely.

## Engineering guardrails

- Do not automatically delete original audit logs or patient/CRM records under the decisions above.
- The indefinite-retention decisions do not override applicable legal requirements, regulatory obligations, valid legal holds, or future written owner instructions. Obtain legal review where required.
- Do not invent retention periods for the still-open data categories.
- Never commit real credentials, encryption keys, .env files, database dumps, real patient data or patient images.
- Keep live AI/SMS integrations disabled until provider identity, secrets, data flows and required approval are verified.
- Record implementation and test evidence separately from policy approval. A documented policy is not proof that a backup job, separate storage volume, or restore process is operational.

**Approval record:** Original owner decisions were recorded from explicit instructions dated 2026-11-03; the booking OTP policy in OD-28-17 was explicitly approved on 2026-11-04. Approver identity/signature and formal legal review remain outside this engineering record.

## Prototype engineering defaults selected by the user (2026-11-05)

The user explicitly delegated remaining Task 28 prototype decisions to the engineering agent and clarified that the immediate target is a local, synthetic-data prototype before real operations. The following are provisional engineering defaults for local/testing only. They are not a legal retention schedule, clinic-owner legal approval, production deployment authorization, or a claim that the periods are universal standards. They supersede the earlier open labels only for the prototype branch.

| Data / workflow | Prototype default | Implementation boundary |
|---|---:|---|
| Consent evidence (consent_records and Smart Smile consent fields) | Keep a 6-year planning baseline after last related processing; do not auto-purge in this prototype | Consent proof is not deleted by the cleanup command. Legal/clinical review must confirm the period and any longer duty before real patient data. |
| Smart Smile bearer access token | 24 hours | Token hash remains stored; expired token cannot read status or upload. Explicit deletion remains available with the original token after expiry. |
| Smart Smile final result payload | 12 months / 365 days from generation | Cleanup clears only the result payload and expiry timestamp; consent evidence/session row is preserved. |
| Smart Smile raw input image | 90 days under existing media policy | Existing media purge workflow remains responsible for file purge; hard purge after soft-delete remains 30 days. |
| Successful queue history | 7 days | Only metadata is recorded (class, queue, attempts, completion time); payloads and patient identifiers are not copied. |
| Failed queue records | 30 days after failure | failed_jobs cleanup is scheduled; pending/running jobs and job_batches are not purged by this policy. |
| Sent notification outbox metadata | 12 months after send | Expired sent rows are removed; no message body is intentionally retained by this policy. |
| Failed notification metadata | 30 days after last attempt | Only stale failed rows with generic error metadata are removed; retrying rows are not age-pruned unless last attempt is older than the window. |
| Deletion/correction request handling | 30-day operational target; closed/rejected request rows 3 years | Pending/under-review requests are never purged. The existing audit log is retained indefinitely. Closing a request does not delete patient/CRM records. |
| Staff/practitioner operational profile after deactivation | 90 days as a planning default | No automated purge until a reliable deactivation timestamp and HR/legal exclusions are modeled. |
| Patient-match flags | Open flags remain until human review; resolved flags target 90 days | No auto-merge/delete. Automated purge remains disabled until a reviewed lifecycle and audit event are available. |
| Booking OTP challenge metadata | 30 days after terminal state | Implemented for consumed, expired, exhausted, invalidated and delivery-failed challenges; active challenges are preserved. |
| Login attempt hashes | 90 days | Existing approved cleanup remains. |
| AI operational metadata | 12 months | Existing cleanup remains; no raw prompts or patient content are retained by this policy. |
| Idempotency records | Existing 24-hour expiry | Existing cleanup remains. |
| Local application logs | 30 days | Existing local daily rotation remains. |
| Original audit logs | Indefinite in the prototype | Existing owner instruction is preserved; no automatic purge. |
| Backups | Daily; 35-day rolling target for a future full DB/files backup; separate encrypted storage | Current code backs up audit logs only, with 90-day copies and monthly integrity verification. Full DB/files backup and physical separation are not established by CI. |
| Recovery objectives | RPO <= 24 hours; RTO <= 8 hours | Planning targets only; require a full isolated recovery exercise. |

### Prototype MFA recovery runbook

For a lost authenticator in a controlled test environment: an active clinic administrator verifies the staff member using a second independent channel and an in-person identity check where practical; the administrator records the reason, revokes all active staff sessions, resets the old TOTP factor, requires enrollment of a new factor, issues new one-time recovery codes for display once, and records an audit event. Never send recovery codes over SMS/email or record them in support tickets. This remains an operational runbook, not an implemented self-service reset feature.

### Provider and deployment defaults

- Keep MeliPayamak and Kavenegar live adapters disabled. Do not inject provider credentials or send live messages in the local prototype; URL-path credential exposure is unresolved.
- Keep live AI providers disabled; use the fake/synthetic provider only.
- Use synthetic patients, phone numbers and images only.
- No production TLS, reverse-proxy, database-role, secret-manager, storage ACL, backup separation, monitoring or restore claim is made.

These choices are engineering defaults selected for prototype progress. Reassess all periods and controls before real patient data, external providers or production deployment.


## Implementation verification note (2026-11-05)

The prototype cleanup now preserves deletion requests under the `legal_hold` resolution code, as well as pending/under-review requests. CI run #1149 passed the synthetic boundary tests for Smart Smile expiry, queue metadata, notification outbox, OTP challenge metadata and deletion-request retention. This does not convert any provisional period into a statutory retention rule or production approval.
