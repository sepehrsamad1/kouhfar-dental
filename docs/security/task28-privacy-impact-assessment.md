# Task 28 — Privacy impact assessment

## Purpose and status

This preliminary privacy impact assessment identifies personal-data processing in the Laravel application before any real patient data is used. It is an engineering inventory, not legal advice, a statutory compliance opinion or approval to process patient data.

**Status: INCOMPLETE — release-blocking owner decisions remain.**

## Processing purposes observed in code

- Staff account authentication and session management.
- Patient registration, lookup, update, mobile reveal and archive.
- Appointment booking, scheduling, confirmation, cancellation and completion.
- Private image upload, display to authorized staff, soft deletion and scheduled purge.
- Smart Smile consent, non-diagnostic simulation and image deletion.
- Staff audit, operational monitoring and AI cost controls.
- SMS notification orchestration through an adapter seam.

No live AI provider or SMS provider is configured in the current environment. The fake AI provider is used in tests; SMS defaults to a local log/null adapter unless explicitly configured otherwise.

## Categories and sensitivity

- Direct identifiers: patient full name and mobile number; staff email and display name.
- Appointment/clinic relationship data: patient-linked appointments, service, practitioner, dates, status and history.
- Image data: uploaded dental/patient images and metadata. Images may be identifying and should be treated as sensitive personal data.
- Consent records: patient link, scope, grant/revoke timestamps and document reference.
- Authentication/security metadata: password hashes, session hashes, IP/email hashes, login events, audit actor/target IDs.
- Operational metadata: queue payloads, failed-job exception text, notification references, idempotency snapshots, AI usage metadata.
- Draft clinical knowledge is not patient data, but editorial approval history identifies staff reviewers.

## Necessity and minimization

Positive evidence:
- Patient list responses mask mobile numbers; explicit mobile reveal is policy-gated and audited.
- AuditLogger allow-lists metadata keys and does not store patient name/mobile/notes in before/after metadata.
- AIRequestRecorder stores approved content IDs and operational metadata, not raw prompt text or user actor IDs.
- AI failure logging is designed to record only feature/capability metadata.
- Media uploads are re-encoded, EXIF metadata is removed, filenames are replaced with UUID keys and storage is private.
- Public booking responses omit the internal appointment UUID.
- No real provider or analytics service is active in the assessed configuration.

Limitations:
- Queue and failed-job payload/exception contents have not been exhaustively inspected.
- The full framework/application log path and production log sink are unknown.
- AI prompt redaction is heuristic and does not establish that all identifying information can be detected. Do not send identifiable patient information to a future provider.
- Media and Smart Smile database metadata may remain after the underlying image is purged.
- Retention and deletion of backups are unknown.

## Access and disclosure

- Staff patient/appointment capabilities are separated by roles and object-level policies.
- Media access is admin-only in the current approved role matrix and requires a temporary signed URL plus authenticated authorization.
- Public AI has no access to patient records, staff notes or internal IDs by design.
- Public Smart Smile status/result access is based on possession of a bearer token; the token is high entropy and only its hash is stored, but no expiry is present.
- A future SMS/AI provider would receive data according to its adapter payload. No live provider data flow is currently active. Kavenegar adapter exists but must remain disabled until clinic approval.
- GitHub Actions receives repository source, synthetic test data and build/test artifacts. CI must never use patient data or production secrets.

## Retention and deletion

Media retention is owner-approved and implemented in the Task 21 service/command path:
- Unconverted Smart Smile/pre-consult uploads: 90 days from upload.
- Patient/clinical media: 2 years anchored to the later of media creation and last historical appointment.
- Early soft-delete allowed; hard purge after the applicable retention window or 30 days after soft-delete.

The following are not yet approved or evidenced: patient/appointment record deletion lifecycle, audit logs, login-attempt hashes, staff session-registry rows, consent records, Smart Smile session/result rows, AI request/usage rows, notification outbox, failed jobs, queue payloads, idempotency snapshots, deletion requests, application logs and backups. See `task28-data-inventory.md`.

## Risks and mitigations

| Risk | Mitigation / status |
|---|---|
| Unauthorized staff access to another patient's records | Role policies, practitioner-scoped queries and regression tests; full endpoint matrix still open. |
| Disclosure through logs/audit/queue/error payloads | Allowlisted audit metadata and safe AI error logging exist; queue/log review and retention approvals remain open. |
| Long-lived Smart Smile bearer token | Hash-at-rest and rate limits exist; token TTL needs owner approval. |
| Excessive retention of operational identifiers | Define owner-approved retention and automated purge schedules before production. |
| Third-party processing without informed approval | Keep real AI/SMS provider disabled until provider, data fields, terms, location, retention and deletion are approved. |
| Secret exposure in Git history or build output | Full-history Gitleaks and dependency scans are being added to CI; scan results/rotation evidence pending. |
| Production backup/log access | Deployment configuration and access model not supplied; release gate remains open. |

## Required owner decisions

1. Approve retention periods for audit, login-attempt, session, queue/failed-job, notification, consent, Smart Smile session/result, AI usage/request, idempotency and deletion-request records.
2. Approve Smart Smile consent-token lifetime and whether status/result access should cease at media purge.
3. Confirm and formally record the patient/appointment clinical-record retention and deletion scope, including exceptions/legal holds and approval responsibilities.
4. Approve backup and log retention, encryption, access, restore and deletion propagation.
5. Before provider activation, approve each provider, exact data fields disclosed, processing purpose, data location, retention, deletion, subcontractors and incident-notification terms.

Until these are answered and implemented, the privacy acceptance criterion is not met and production use with real patient data is blocked.
