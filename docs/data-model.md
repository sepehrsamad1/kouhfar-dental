# Data Model (Package 3)

Conceptual models from the Package 3 prompt. Final tables follow docs/product-spec.md. OPEN = depends on an owner decision.
All timestamps: `timestamptz`, stored UTC, returned as ISO 8601. Primary keys: UUID (time-sortable v7). No fees stored (D14).

## Identity and access
| Table | Key columns | Constraints and notes |
|---|---|---|
| StaffUser | id, email (citext), display_name, password_hash, role, is_active, created_at, updated_at | unique(email). Role: admin, reception, practitioner. No public insert. |
| Session | id, user_id, token_hash, created_at, expires_at, revoked_at, last_seen_at | unique(token_hash). index(user_id). Expired rows purged by job. |
| LoginAttempt | id, identifier_hash, ip_hash, created_at, success | Lockout by identifier and IP. Hashes only. |
| AuditLog | id, actor_id (nullable), action, target_type, target_id, occurred_at, request_id, meta (jsonb, allowlisted keys) | Append-only: app role has no UPDATE or DELETE. Meta never holds secrets or note text. |

## Patients
| Table | Key columns | Constraints and notes |
|---|---|---|
| Patient | id, full_name, mobile_normalized, date_of_birth (nullable), notes_sensitive (nullable), archived_at, created_at, updated_at | NOT unique on mobile (family sharing). index(mobile_normalized). Notes: admin only. |
| PatientMatchFlag | id, patient_a_id, patient_b_id, reason, status (open, dismissed, merged), created_at | CHECK patient_a_id < patient_b_id. Never auto-merged. |

Mobile normalization: Persian and Arabic digits to Latin, strip separators, store `09xxxxxxxxx`. Validated server-side.

## Catalog and calendar
| Table | Key columns | Constraints and notes |
|---|---|---|
| Service | id, name_fa, duration_minutes, buffer_before_minutes, buffer_after_minutes, is_active, is_public_bookable | CHECK duration > 0, buffers >= 0. Deactivate, never delete if referenced. |
| Practitioner | id, display_name_fa, is_active, user_id (nullable) | user_id links practitioner role. |
| PractitionerService | practitioner_id, service_id | PK(practitioner_id, service_id). |
| Resource | id, name_fa, kind, is_active | Chair or room. |
| ServiceResource | service_id, resource_id, quantity | PK(service_id, resource_id). |
| WorkingHours | id, practitioner_id (nullable = clinic), weekday, start_local, end_local | CHECK end > start. Local times in clinic timezone (D15). |
| ScheduleException | id, practitioner_id (nullable), starts_at, ends_at, kind (holiday, leave, closed), reason | Conflicts with appointments are reported, not auto-moved. |

## Bookings
| Table | Key columns | Constraints and notes |
|---|---|---|
| Appointment | id, patient_id, service_id, status, start_at, end_at, source (public, staff), expires_at, created_by, created_at, updated_at, version | status: pending, confirmed, completed, cancelled, no_show, expired. CHECK end_at > start_at. CHECK expires_at IS NOT NULL when status = pending. version for optimistic concurrency. |
| AppointmentPractitioner | appointment_id, practitioner_id, occupied (tstzrange), active | EXCLUDE USING gist (practitioner_id WITH =, occupied WITH &&) WHERE (active). |
| AppointmentResource | appointment_id, resource_id, occupied (tstzrange), active | EXCLUDE USING gist (resource_id WITH =, occupied WITH &&) WHERE (active). |
| AppointmentEvent | id, appointment_id, actor_type (staff, public, system), actor_id, from_status, to_status, occurred_at, reason | Append-only history. |
| FollowUpToken | id, appointment_id, token_hash, action_scope (view, cancel, reschedule), expires_at, used_at | One booking, one action scope. Hash only. |

`occupied = tstzrange(start_at - buffer_before, end_at + buffer_after, '[)')`, computed on the server.
`active = true` only for pending and confirmed. Status and `active` change in one transaction.

## Verification (only if D8 approved; OPEN)
| Table | Key columns | Constraints and notes |
|---|---|---|
| VerificationChallenge | id, mobile_normalized, code_hash, purpose, attempts, max_attempts, expires_at, verified_at, created_at | Codes hashed. Resend interval enforced. Grant bound to one slot, short TTL. |

## Content and media
| Table | Key columns | Constraints and notes |
|---|---|---|
| ContentItem | id, kind (service, faq, case, doctor, clinic_info), locale, body (jsonb), status (draft, published, archived), published_at | Public API reads published items only. |
| MediaAsset | id, storage_key (random UUID), mime_type, byte_size, sha256, owner_patient_id (nullable), kind (case_before, case_after, smile_input, other), created_by, created_at, deleted_at | Never public. Only the authorized endpoint reads it. |
| ConsentRecord | id, patient_id, scope, granted_at, revoked_at, document_ref | Active consent required for case and smile images (OPEN: policy). |

## AI
| Table | Key columns | Constraints and notes |
|---|---|---|
| AIRequest | id, feature (assistant, smile_sim), actor_type, actor_id (nullable), status, created_at, content_ids (array) | No raw patient text stored unless approved (OPEN). |
| AIUsage | id, day (date), feature, input_tokens, output_tokens, cost_estimate, request_count | unique(day, feature). Caps enforced against this table. |

## Operations
| Table | Key columns | Constraints and notes |
|---|---|---|
| Job | id, type, payload (jsonb), status (queued, running, done, failed, dead), attempts, run_at, locked_until | index(status, run_at). SKIP LOCKED claim. |
| NotificationOutbox | id, channel (sms), recipient_hash, template, payload_ref, status, attempts, created_at, sent_at | Adapter-based. Dev provider blocked in production. |
| IdempotencyRecord | id, owner_scope, key, request_hash, response_status, response_body (jsonb), created_at, expires_at | unique(owner_scope, key). |
| DeletionRequest | id, target_type, target_id, requested_by, approved_by, status, created_at | Required for any hard delete (OPEN: D11). |

## Integrity summary
- Foreign keys on every reference. `ON DELETE RESTRICT` for patients, services, practitioners, resources referenced by appointments.
- Booking conflicts enforced by EXCLUDE constraints, not application checks alone.
- Migrations are forward-only. Destructive changes need approval (Package 8 rule).

## Task 07 entity naming decision

Use one generic `Job` entity for queued background work. Image processing is represented by a job type; `ImageJob` is not a separate table or model. This reconciles the Task 03 entity-list wording with this document's Operations schema and the architecture's Postgres-backed queue. Task 07 adds only the model shape; queue execution and database schema remain out of scope here.
