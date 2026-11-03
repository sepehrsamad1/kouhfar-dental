# Task 28 — Retention implementation review

Reviewed against branch code at commit `2b192bb7f29cf1b801cbe1f7d52faca7adbaf7d9`. This is a source inspection only; no application tests or production data were run.

## Findings

### R28-RET-01 — Smart Smile result and consent evidence share one lifecycle
- `smart_smile_sessions` stores consent token hash, consent version, consent timestamp, revocation timestamp and result JSON in one row.
- `SmartSmileService::grantConsent()` creates a token hash but no expiry timestamp.
- `SmartSmileService::resolve()` resolves solely by token hash; it does not enforce expiry.
- `SmartSmileService::delete()` clears the result and soft-deletes the media asset while retaining the session row.
- The final output's selected 12-month retention cannot safely be implemented by deleting the whole session, because that would also remove consent evidence.
- Safe design: expire/clear only the result payload after 12 months, retain the minimal consent evidence separately under its own approved retention rule, and enforce token expiry/revocation independently. The current schema needs a separate result timestamp/expiry and a consent-evidence lifecycle before this is complete.

### R28-RET-02 — Smart Smile input media uses the existing 90-day media rule
- `MediaAssetService::eligibleForPurge()` applies 90 days to assets without a patient owner, and two years anchored to the later of media creation and last appointment for patient-owned media.
- Smart Smile uploads are stored with `owner_patient_id = null` and `MediaAssetKind::SmileInput`, so the existing 90-day rule currently applies.
- Soft-deleted media is hard-purged after 30 days.
- This supports the 90-day raw/pre-consultation input policy, subject to checking the media purge command and storage lifecycle end-to-end.

### R28-RET-03 — Queue and failed-job retention are not implemented
- Database queue rows use integer Unix timestamps in `jobs.created_at`; failed jobs use `failed_jobs.failed_at`.
- `PruneExpiredSecurityData` currently prunes login attempts, AI operational rows and expired idempotency records only.
- No 7-day successful-job or 30-day failed-job cleanup is present.
- Cleanup must avoid deleting reserved/running jobs. It should delete only old, unreserved database queue rows and failed-job records older than the policy window. Batch metadata is a separate category and should not be purged without a policy.

### R28-RET-04 — Notification outbox lacks a precise failure timestamp
- `notification_outbox` includes `scheduled_at`, `sent_at`, `attempts` and `last_error`, but no `last_attempt_at` or `failed_at`.
- Successful metadata can be aged from `sent_at` for the proposed 12-month period.
- Failed-notification metadata cannot be aged accurately from the last failure because `last_error` is overwritten without a corresponding timestamp. Using `scheduled_at` as a substitute would be inaccurate.
- Add a timestamp field and update the sender's success/failure paths before implementing the 30-day failed-notification retention.

### R28-RET-05 — Deletion request lifecycle is a placeholder
- `DeletionRequest` stores target type/id, requester, approver, status and creation time.
- No execution timestamp, completion outcome, exception reason, or propagation evidence is represented in the model shown.
- A 30-day handling target must be treated as an operational SLA, not a scheduled deletion of patient/CRM records. The prior owner decision to retain patient/CRM history indefinitely remains a separate rule and must not be overridden by a deletion-request cleanup.
- Implement a reviewed workflow with legal exceptions, scoped actions, audit events and backup-restoration handling before automation.

### R28-RET-06 — Consent record and Smart Smile consent are distinct structures
- `consent_records` contains patient, scope, grant/revoke timestamps and document reference.
- Smart Smile also stores session-specific consent version/timestamp/token hash.
- No verified linkage or retention expiry is shown between these structures.
- Do not automatically prune either structure until the master prompt's actual consent-evidence period and legal requirements are confirmed from the source and reconciled with the clinic's existing record-retention decision.

## Next engineering sequence

1. Add separate Smart Smile output expiry without deleting consent evidence or prematurely deleting the session.
2. Add safe cleanup for old unreserved successful queue rows (7 days) and failed-job rows (30 days), with deterministic tests.
3. Add `last_attempt_at` / failure timestamp to notification outbox, populate it on every attempt and implement distinct 12-month sent / 30-day failed retention.
4. Complete deletion-request lifecycle and audit model before implementing request retention or any data deletion.
5. Keep consent evidence and token expiry separate; do not guess a consent retention duration or token TTL where the source/owner record does not supply one.
6. Add tests for boundary timestamps, active/reserved jobs, retries, soft-delete/hard-purge behavior, and idempotent repeated cleanup.

## Verification and release boundary

No code changes were made in this review step. No test run was triggered. Existing CI run #937 predates this review document and does not verify any new implementation.

Task 28 remains blocked by SEC-28-003 and the other documented release gates. This review does not establish production readiness, legal compliance or ASVS certification.
