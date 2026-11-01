# Task 26 — Smart Smile

Status: IMPLEMENTED — awaiting final CI verification.

## Scope
- Smart Smile remains a non-diagnostic simulation.
- Explicit consent is required before an image-processing token is issued.
- Uploaded images use the Task 21 private media disk and \`MediaAssetService\`.
- Processing is queued through \`ProcessSmartSmile\`.
- Tests use synthetic images and the Task 23 fake image-analysis provider only.
- Deletion revokes consent and soft-deletes the private image.
- The approved Task 21 90-day retention policy is enforced by the existing \`media:purge-expired\` scheduler/job path.
- Feature flags default to disabled outside CI:
  - SMART_SMILE_ENABLED=false
  - SMART_SMILE_RELEASE_GATE=false

## Safety boundary
The result is deliberately fixed to non-diagnostic wording. No diagnosis, disease prediction, treatment recommendation, dosage, guarantee, or clinician impersonation is exposed.

## Tests
Task-specific coverage includes:
- release-gate fail-closed behavior
- explicit consent
- invalid image validation
- private storage
- queued processing
- fake-provider processing
- safe failure
- deletion/revocation
- unauthorized token access
- browser normal flow, consent refusal, deletion, safe failure handling
- responsive browser evidence at 375px, 768px, and 1440px

No real patient image or credential is used.
