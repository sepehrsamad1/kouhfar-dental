# Task 21 — Secure Image & Media System

Status: IMPLEMENTED ON BRANCH — pending CI evidence and merge.

## Owner-approved policy
- Smart Smile / pre-consultation uploads: 90 days from upload date when not converted to a patient chart.
- Patient / clinical records: 2 years (24 months), anchored to the later of media creation and the patient's last historical appointment.
- Authorized staff may soft-delete earlier on patient request or clinic discretion.
- Hard purge: after the applicable retention window, or 30 days after soft-delete.
- Allowed MIME types: JPEG, PNG, WebP.
- Maximum size: 10 MB per image.
- Images are re-encoded with PHP GD and stored under random UUID filenames; EXIF metadata is not carried into the re-encoded output.
- Storage is private; downloads require an authenticated authorized staff user plus a 15-minute temporary signed URL.

## Implementation
- MediaAssetService performs content/MIME validation, image decoding, dimension/bomb protection, re-encoding, hashing and private storage.
- MediaAssetController provides staff upload, signed download and authorized soft-delete.
- PurgeMediaAsset is the queued hard-purge job.
- media:purge-expired is the Task 22 scheduler hook; Task 21 does not choose a scheduler frequency.
- The owner-approved 10 MB per-image limit is enforced. No unapproved aggregate storage quota was invented.
- No real patient images are used by tests.

## Security boundaries
- The private media disk is separate from the public disk and is not directly served.
- Upload validation uses detected content MIME plus image decoding; client filenames/extensions are ignored.
- Random UUID storage keys are generated server-side.
- Downloads are authorization-gated and require a temporary signed URL.
- Soft-deleted media is not downloadable.
- Hard purge removes the private storage object and then permanently removes the database row.
- Current media policy remains admin-only because no broader reception/practitioner media access matrix has been approved.

## Deliberate omissions
- Task 22 must wire media:purge-expired into the Laravel Scheduler/queue operations.
- No production migration, deployment, external media provider, or real patient data was introduced.
