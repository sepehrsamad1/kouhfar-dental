# Task 28 — Clinic-owner decisions required for security and privacy closure

**Status: OPEN — this document records decisions that engineering must not invent.**

Task 21's already-approved media policy remains unchanged:
- Unconverted Smart Smile / pre-consultation uploads: 90 days from upload.
- Patient / clinical media: 2 years, anchored to the later of media creation and the patient's last historical appointment.
- Authorized early soft-delete; hard purge after the applicable retention window or 30 days after soft-delete.

The decisions below are separate from that approved media policy. No real patient data is in use; these decisions are required before production use with real patient data.

## Decisions needed

| ID | Data / control | Existing evidence | Owner decision required |
|---|---|---|---|
| OD-28-01 | Clinical-record scope and deletion behavior | A prior owner instruction specifies 2-year clinical-record retention from creation / last appointment. Patient and appointment records currently do not have a complete purge/anonymization workflow. | Confirm whether the 2-year period applies to patient identity, appointments, appointment events, patient-match flags and consent evidence. For records linked by foreign keys, choose deletion, irreversible anonymization, or another approved disposition. Define any legal/clinical hold exception. Do not re-approve the already-set 2-year period unless its scope changes. |
| OD-28-02 | Staff session lifetime | Staff idle and absolute timeouts are unset; session registry expiry currently falls back to 10 years. Deactivated staff sessions are now revoked immediately. | Approve idle timeout and absolute timeout values, and how long revoked/expired registry rows are retained before purge. |
| OD-28-03 | Login-attempt metadata | login_attempts stores hashed email/IP identifiers, outcome and timestamp. No purge schedule exists. | Approve retention period and purge timing for these hashes. |
| OD-28-04 | Audit logs | Audit rows contain actor/target UUIDs, action, request ID, timestamp and allow-listed metadata; target UUIDs may remain linkable to patients. | Approve retention period and whether identifiers should be pseudonymized after the operational audit period. |
| OD-28-05 | Consent and Smart Smile records | Consent evidence has no approved retention period. Smart Smile bearer tokens are hashed but do not expire; session/result rows have no expiry or purge job. | Approve consent-token TTL; behavior after expiry; retention for Smart Smile session/result metadata; retention for consent evidence/document references; and whether revocation triggers immediate media/session deletion or only blocks access. |
| OD-28-06 | Queue, notification and idempotency data | Queue/failed-job payloads, job batches, notification outbox/error metadata and deletion requests have no approved retention. Idempotency rows have a 24-hour expiry field/design but no purge job. | Approve retention after job completion/failure and after notification delivery; approve deletion-request retention; confirm whether idempotency responses should be purged after the existing 24-hour expiry. |
| OD-28-07 | AI operational metadata | AI request/usage rows store provider/model/feature, token/cost metrics and approved content IDs, not raw prompts. No purge schedule exists. | Approve retention for AI request and usage metadata, including content references. |
| OD-28-08 | Application and infrastructure logs | Application log sink is deployment-dependent; production destination, access, redaction and retention are unknown. | Approve log retention and access roles, and provide the intended log sink/configuration for review. Confirm request bodies, identifiers, tokens, OTPs and clinical notes must never be logged. |
| OD-28-09 | Backups | Production database/media backup provider, encryption, access and retention are unknown. | Approve backup retention, encryption/access requirements, restore testing ownership and deletion propagation after source-record purge. |
| OD-28-10 | Staff/practitioner profiles and patient-match flags | Staff/practitioner identifiers, names and schedules are stored. patient_match_flags.reason is free text and may contain patient-identifying or clinical detail; no dedicated route/controller was found. | Approve account/profile retention after deactivation and whether patient-match flags are required. If retained, approve access roles and a minimal reason-code vocabulary rather than free-text clinical notes. |

## Engineering guardrails while decisions are pending

- Keep real patient data disabled in production and staging until this register is approved and implemented.
- Do not invent TTLs, retention periods, anonymization behavior or legal-hold rules.
- Do not purge records or run destructive database operations based on draft assumptions.
- Continue to enforce access controls, private storage, minimal logging and feature/provider disablement.
- Once decisions are approved, implement idempotent purge/anonymization jobs with audit-safe behavior and regression tests, then re-run the security suite.

**Approval record:** placeholder — clinic owner to confirm each OD-28 decision, the approver identity/role and approval date. Do not put patient data or personal credentials in this document.
