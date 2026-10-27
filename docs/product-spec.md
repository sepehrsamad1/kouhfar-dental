# Product Spec (Package 2)

Status: DRAFT. FIXED = stated by owner or by the existing UI. ASSUMPTION = proposed default, not approved. OWNER DECISION = blocks implementation of that area. Later explicit owner decisions supersede earlier proposed defaults where noted.
Sources: docs/ui-audit.md (Package 1), the source UI HTML, the Package 2 prompt.

## 1. Product areas

| Area | Scope | Current state |
|---|---|---|
| Public site | Hero, services, results, smile entry, doctor, FAQ, contact, booking entry | Static HTML/JS. Results and smile are demo content. |
| Booking core | Availability, appointment request, confirmation, lifecycle | Booking UI is simulated. No backend. |
| Staff panel | Login, patients, calendar, services, practitioners, settings, audit | Not started. |
| AI capabilities | Chat assistant, smile simulation | Chat is keyword matching (demo). Smile is canned (demo). Real AI is Phase 8. |

## 2. Required decisions

| # | Decision | Status | Proposed default (ASSUMPTION unless noted) |
|---|---|---|---|
| D1 | Number of practitioners | OWNER DECISION | 1 practitioner (Dr. Arash Kouhfar); model supports N |
| D2 | Chairs / bookable resources | OWNER DECISION | 1 chair; resources modeled but optional |
| D3 | Services, durations, buffers | OWNER DECISION | Buffer 10 min default; durations not published |
| D4 | Working hours and holidays | OWNER DECISION | Not assumed. Online booking stays closed until supplied |
| D5 | Request vs confirmed booking | ASSUMPTION | Public booking creates `pending`; staff confirms |
| D6 | Expiry of unconfirmed requests | OWNER DECISION | Proposed 2 hours; owner to choose |
| D7 | Phone bookings entered by staff | ASSUMPTION | Staff can create `confirmed` directly |
| D8 | Mobile verification (OTP) | OWNER DECISION | Recommended yes for public requests; SMS provider not chosen |
| D9 | Follow-up (view/cancel) access | ASSUMPTION | Secure token; re-verify via OTP. No lookup by phone alone |
| D10 | Cancellation and rescheduling cutoff | OWNER DECISION | Proposed 24 h before start; value needed |
| D11 | Retention of patients, appointments, images | OWNER DECISION | No deletion until approved |
| D12 | Staff access to sensitive data | OWNER DECISION | Least privilege per section 5a (proposed) |
| D13 | External service budget and terms | OWNER DECISION | None chosen (SMS, AI, hosting) |
| D14 | Public display of prices or fees | OWNER DECISION | No fees shown until approved |
| D15 | Clinic timezone | ASSUMPTION | Asia/Tehran (inferred from Persian UI; owner to confirm). Stored in UTC |
| D16 | Calendar display | ASSUMPTION | Jalali for display; ISO 8601 in API |

## 3. Booking states

| State | Meaning | Occupies capacity? | Entry | Exit |
|---|---|---|---|---|
| pending | Request received, not confirmed | Yes, until expiry | Public request, or staff create-as-pending | confirmed, cancelled, expired |
| confirmed | Staff or verified confirmation | Yes | From pending, or staff direct create | completed, cancelled, no_show |
| completed | Visit happened | No | From confirmed, after start time | none |
| cancelled | Cancelled by patient or staff | No | From pending or confirmed | none (terminal) |
| no_show | Patient did not attend | No | From confirmed, after start time | none (terminal) |
| expired | Pending request not confirmed in time | No | From pending, by scheduled job | none (terminal) |

Capacity: `pending` and `confirmed` occupy capacity. All other states release it.

Pending expiry mechanism (ASSUMPTION; duration is D6):
- Each pending booking stores `expires_at` at creation.
- A scheduled job (Package 22) moves overdue pending bookings to `expired`. It runs on a fixed interval and is idempotent, so a restart cannot lose or double-expire a request.
- A pending booking is never auto-confirmed.
- Staff can confirm only before `expires_at`. An expired booking is not revived; the patient must request again.

Reschedule is not a state. It is one atomic slot change on a `confirmed` booking, recorded as an event. If the destination is full, the original stays unchanged.

Rules:
- Only a confirmed booking can become completed or no_show.
- Every transition writes an AppointmentEvent (actor, time, from, to) in Package 3.

## 4. Roles and permissions (proposed; OWNER DECISION on roles)

| Role | Purpose |
|---|---|
| admin | Staff accounts, roles, settings, services, schedule, audit log |
| reception | Patients, bookings (create, confirm, reschedule, cancel), calendar view |
| practitioner (optional) | Own calendar and appointments; no admin rights |
| public | Availability (no names), create request, verify and follow up via token |

| Permission | admin | reception | practitioner | public |
|---|---|---|---|---|
| Manage staff users and roles | yes | no | no | no |
| Manage services, practitioners, schedule | yes | read only | read own | read active services |
| View availability (no personal data) | yes | yes | yes | yes |
| View and edit patient records | yes | yes | own patients only (if approved) | no |
| Archive patient record | yes | no | no | no |
| Create / confirm / reschedule / cancel bookings | yes | yes | no | own booking only, via token |
| Mark completed / no_show | yes | yes | own only | no |
| View audit log | yes | no | no | no |
| Upload or delete images | yes (after consent check) | no | no | no |

All checks run on the server. Hiding a button is not access control.

## 4a. Public booking flow (ASSUMPTION; depends on D5, D8, D9)

1. Visitor picks an active service (no login).
2. Visitor picks a date; server returns free slots only (no patient data).
3. Visitor picks a slot and enters name and mobile number.
4. If D8 is approved: OTP sent and verified; it binds the request to that one slot for a short time.
5. Server creates a `pending` booking with `expires_at` and returns a follow-up token. No patient-visible record is returned.
6. Staff confirms or rejects in the staff panel.
7. Patient cancel or reschedule: only with the follow-up token plus re-verification, and only before the D10 cutoff.

If D8 is not approved, step 4 is removed and the spec is re-reviewed for abuse risk before build.

## 5. Patient data (minimum; ASSUMPTION until D11/D12)

- Required: full name, mobile number (normalized), date of birth optional, notes marked sensitive.
- Mobile number is NOT a unique identity: family-shared numbers are possible. Duplicates are surfaced for staff, never auto-merged.
- No public endpoint returns patient data.

## 5a. Patient-data access policy (ASSUMPTION until D11/D12)

| Data | Who can read | Who can write | Logged on read? | Public? |
|---|---|---|---|---|
| Patient name, mobile | admin, reception | admin, reception | Proposed: yes for search results | No |
| Patient notes (free text) | admin only (proposed) | admin only | Yes | No |
| Appointment list per patient | admin, reception | admin, reception | Yes on export | No |
| Availability (times only) | everyone | system | No | Yes, no names |
| Images (case, smile) | admin only, after consent check | admin, after consent check | Yes | Never |

Search is paginated and capped. Near-matches are shown to staff and never auto-merged.

## 6. Requirements (IDs)

| ID | Area | Requirement |
|---|---|---|
| R-PUB-01 | Public | Services and booking entry visible without login. |
| R-PUB-02 | Public | Availability shows only free slots; no patient data. |
| R-PUB-03 | Public | Public request creates `pending` only, never `confirmed`. |
| R-PUB-04 | Public | Mock reference and fake confirmation removed before go-live (Package 16). |
| R-PUB-05 | Public | If D8 approved, public request requires verified OTP; otherwise spec re-reviewed before build. |
| R-BK-01 | Booking | Lifecycle follows section 3 exactly. |
| R-BK-02 | Booking | Server computes end time and duration from the service. |
| R-BK-03 | Booking | Double booking of practitioner or resource prevented by the database, not the UI. |
| R-BK-04 | Booking | Pending requests expire automatically and free their slot. |
| R-BK-05 | Booking | Reschedule is atomic. |
| R-BK-06 | Booking | Patient cancel or reschedule only with valid token and before D10 cutoff. |
| R-BK-07 | Booking | Staff can create confirmed bookings directly (D7). |
| R-STF-01 | Staff | Staff login only; no public registration. |
| R-STF-02 | Staff | Each role limited to section 4 permissions, enforced on server. |
| R-STF-03 | Staff | Every patient and appointment change audited, without secrets or medical text. |
| R-STF-04 | Staff | Staff lists (patients, bookings) paginated and filterable by status and date. |
| R-PAT-01 | Patient | Search paginated and capped. Near-matches shown, not auto-merged. |
| R-DAT-01 | Data | No hard deletion of patients, appointments, or images without approved retention policy (D11). Archive only. |
| R-SEC-01 | Security | Passwords hashed (Argon2id), sessions server-side, CSRF protected. |
| R-AI-01 | AI | Chat and smile labeled demo until Phase 8 is complete. |
| R-AI-02 | AI | Chat answers only from approved clinic content; no diagnosis or medical advice. |
| R-AI-03 | AI | Smile output is a simulation, never a clinical result, until owner approves a medical policy. |

## 7. Out of scope for Package 2
- Code, database, or API changes.
- Real SMS, real AI, payments, or fee display.
- Medical claims or treatment recommendations from the smile feature.

## 8. Prioritized questions for the owner (blocking first)
1. (Blocks Package 3) How many practitioners and chairs? Is booking tied to one practitioner?
2. (Blocks Package 3) Service durations and buffers? Which services are bookable online?
3. (Blocks Package 14) Pending expiry: how long (D6)?
4. (Blocks Package 15) Use SMS OTP for public requests (D8)? Which provider, what budget?
5. (Blocks Package 13) Working hours, holidays, and how far ahead patients can book (D4)?
6. (Blocks Package 10) Approve the role table in section 4, including whether practitioners log in?
7. (Blocks Package 28) Retention for patients, appointments, and images (D11)?
8. (Blocks Package 21) Are the before/after case images real patients, and is consent documented?
9. (Blocks Package 14) Cancel/reschedule cutoff (D10)?
10. (Confirm) Clinic timezone (D15)?
