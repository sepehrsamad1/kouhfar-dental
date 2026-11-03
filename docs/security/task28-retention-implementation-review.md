# Task 28 — Retention implementation review

Reviewed against branch `task/28-security-privacy-audit`. The metadata-only completed-job history groundwork below passed CI run [#979](https://github.com/sepehrsamad1/kouhfar-dental/actions/runs/41684593114) on revision `3282ab38d55df8d3b5cd7cb01a70ed6aada4821c`. No production data was used. No production data was used.

## Findings

### R28-RET-04 update — notification attempt time is now recorded
- Added nullable indexed `last_attempt_at` to `notification_outbox`.
- `SendAppointmentNotification` records UTC attempt time and increments attempts immediately before provider dispatch; successful delivery still records `sent_at` and clears `last_error`.
- Added a regression assertion that a retry followed by successful delivery leaves a non-null attempt timestamp.
- Failed-job callback still records a generic failure message after retries are exhausted. A dedicated `failed_at` is not added separately because `last_attempt_at` identifies the last provider attempt; policy-specific retention remains disabled.
- The owner decision register still leaves outbox/error retention open. Do not activate 12-month/30-day deletion until the owner approves the policy and message-body/data classification is verified.


### R28-RET-03 update — metadata-only completed-job history groundwork
- Added `completed_job_history` with only job class, queue name, attempt count and UTC completion timestamp. It intentionally stores no serialized payload, exception text, patient identifier or job arguments.
- Registered a Laravel queue-after callback to record successfully processed jobs. Failed jobs are not written to this completed table; the existing `failed_jobs` store remains separate.
- Added a model contract test. End-to-end event behavior and migration execution await the next CI run.
- The proposed seven-day retention period is not activated because queue-history retention remains an open owner decision.

### R28-RET-03 update — failed queue job cleanup added
- `security:prune-expired-data` now deletes `failed_jobs` rows whose `failed_at` is older than 30 days.
- The cleanup does not touch `jobs` rows, including reserved/running work.
- Laravel's database queue removes successfully completed jobs from `jobs`; there is no completed-job history table in this configuration. Therefore a 7-day successful-job history retention cannot be implemented against the current schema without introducing a separate job-history store. `job_batches` is a distinct table and is not pruned because its retention policy is not specified.
- A dedicated regression test for the new failed-job branch has not yet been committed. CI must pass before this change is considered verified.


### R28-RET-01 update — result timestamp added
- Added nullable, indexed `result_generated_at` to `smart_smile_sessions` in migration `2026_11_03_000001_add_result_generated_at_to_smart_smile_sessions_table.php`.
- Successful `ProcessSmartSmile` completion writes the UTC generation timestamp. Queue start, failed processing, new upload and user revocation clear it.
- The model casts the field as immutable datetime; the factory and feature test cover the new state.
- This is metadata only. No automatic 12-month purge is enabled, and consent token TTL/evidence retention remain unchanged and unresolved.


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

### R28-RET-03 — queue history and failed-job cleanup status
- Successful-job metadata history is now represented by `completed_job_history`; only class, queue name, attempts and completion timestamp are captured.
- Failed queue payloads older than 30 days are pruned by the current command. A dedicated regression test for that branch is still missing.
- No retention cleanup is enabled for `completed_job_history`, `jobs` or `job_batches`. The proposed seven-day successful history and 30-day failed payload windows remain subject to explicit owner policy approval; running/reserved jobs and batch metadata must not be deleted speculatively.

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

1. Verify completed-job metadata event capture and migration in CI, then add boundary/retention tests after policy approval.
2. Add a precise failure timestamp for notification delivery and tests; keep purge disabled until retention is approved.
3. Complete the deletion-request review/audit lifecycle without automatically deleting patient or CRM records.
4. Keep Smart Smile result expiry separate from consent evidence and token expiry.
5. Keep consent evidence and token expiry separate; do not guess a consent retention duration or token TTL where the source/owner record does not supply one.
6. Add tests for boundary timestamps, active/reserved jobs, retries, soft-delete/hard-purge behavior, and idempotent repeated cleanup.

## Verification and release boundary

The Smart Smile result timestamp migration, notification attempt tracking, and completed-job metadata groundwork are committed on the task branch. CI run #968 passed commit `f3b91292bede46ce0e82c6e30291adb78a606e9b`, but predates the latest completed-job history commits; a new CI run is required to verify them.

Task 28 remains blocked by SEC-28-003 and the other documented release gates. This review does not establish production readiness, legal compliance or ASVS certification.
