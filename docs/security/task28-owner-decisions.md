# Task 28 — Clinic-owner decisions for security and privacy closure

**Status: PARTIALLY APPROVED — decisions below are recorded from the clinic owner's instructions on 2026-11-03. This is an engineering decision record, not legal advice or production approval.**

## Approved decisions

| ID | Area | Owner decision | Engineering consequence / boundary |
|---|---|---|---|
| OD-28-01 | Patient and clinical data retention | Retain patient data indefinitely for CRM and future clinic use. | Do not automatically delete or anonymize patient, appointment, or related CRM records. This does not override applicable law, a valid legal hold, or a later written owner instruction. No destructive purge job is authorized by this decision. |
| OD-28-02 | Staff session lifetime | 15-minute idle timeout and 8-hour absolute timeout. | Configure both values server-side. A staff session must be revoked when either limit is exceeded. |
| OD-28-04 | Audit-log retention | Retain original audit logs indefinitely. | Do not purge original audit-log rows automatically. Audit logs remain access-controlled and must not contain patient names, mobile numbers, clinical notes, secrets, or raw request bodies. |
| OD-28-08 | Application log destination | Store application logs locally for now. | Do not add an external SIEM/logging provider. Production log access, redaction and rotation still require deployment verification. |
| OD-28-09 | Audit-log backup | Back up audit logs daily; keep backup copies for at least 90 days; store them separately from the primary application/database storage; encrypt them; restrict access to authorized administrators; and perform a restore/integrity test monthly. | The 90-day period applies only to backup copies. It does not authorize deletion of original audit logs. The separate storage location and encryption key must be supplied through deployment secrets/configuration, never committed. Verify that the configured destination is a genuinely separate storage volume/service, not merely another folder on the same disk. |
| OD-28-11 (partial) | SMS/OTP capability | An SMS service has already been purchased and OTP is ready for backend integration. | The provider name and credentials have not been supplied in this decision record. Keep live sending disabled until the exact provider, data flow, credential handling and relevant OTP use case are verified. This decision alone does not approve staff MFA or its recovery policy. |
| OD-28-12 | AI architecture | AI models will be added. The architecture must remain provider-independent. The API key will be supplied later through a secure channel. | Keep provider adapters/configuration abstracted and credentials server-side. Do not commit real API keys. AI remains disabled until the chosen provider/model, pricing, safety evaluation and deployment configuration are verified and explicitly activated. |
| OD-28-13 | Session cookies | Secure and HttpOnly must be enabled. SameSite=Lax is the default unless compatibility tests demonstrate that Strict works without breaking required flows. | Keep Secure enforced outside local/testing, HttpOnly enabled, and SameSite=Lax for now. Do not switch to Strict without regression evidence. |
| OD-28-14 | Rate limiting | Use endpoint-specific limits: strict for staff login and OTP, flexible for public read-only pages, and appropriate limits for booking. Apply IP/account-based throttling and anti-abuse controls where appropriate. | Keep limits server-side and test them. OTP limits must prevent SMS abuse and unexpected cost. Distributed multi-node enforcement remains a deployment verification item. |

## Decisions still open

| ID | Area | Still required |
|---|---|---|
| OD-28-03 | Login-attempt hashes | Retention and purge policy for hashed email/IP login-attempt metadata. |
| OD-28-05 | Consent and Smart Smile | Consent-token TTL, post-expiry behavior, retention of consent evidence and Smart Smile session/result metadata, and revocation behavior. |
| OD-28-06 | Queue and operational records | Retention for queue/failed-job payloads, notification outbox/error metadata, deletion requests, and idempotency snapshots. The existing 24-hour idempotency expiry design still needs an implemented cleanup policy. |
| OD-28-07 | AI operational metadata | Retention period for AI request/usage records and content references. |
| OD-28-08 (partial) | Application logs | Local destination is approved; log rotation/retention duration and production access controls still need deployment confirmation. |
| OD-28-09 (deployment) | Backup implementation | The policy is approved, but the actual separate storage destination, encryption key provisioning, backup execution evidence, monitoring and restore-test evidence are not yet present in the repository. |
| OD-28-10 | Staff/practitioner profiles and patient-match flags | Account/profile retention after deactivation and whether patient-match flags are required; if retained, approve access roles and a controlled reason-code vocabulary. |
| OD-28-11 (partial) | Staff MFA | Confirm whether staff MFA is required, which factor applies to each role, enrollment, lost-device/account recovery, backup codes, administrator reset authority and provider-outage behavior. A purchased SMS service is not by itself approval of an MFA recovery policy. |
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

**Approval record:** Owner decisions recorded from the clinic owner's explicit instructions dated 2026-11-03. Approver identity/signature and formal legal review remain outside this engineering record.
