# Acceptance Criteria (Package 2)

Every requirement in docs/product-spec.md section 6 has a row here. Status is "pending" until the implementing package is verified. Nothing is marked done.

| ID | Requirement | Acceptance test | Status |
|---|---|---|---|
| R-PUB-01 | Services and booking entry visible logged out | Open site logged out; services and booking CTA render | pending |
| R-PUB-02 | Availability response has no patient data | Public availability response contains no names or phones | pending |
| R-PUB-03 | Public request is `pending` | Submit public request; stored status is `pending` | pending |
| R-PUB-04 | No mock reference or fake confirmation | Production build contains no mock reference generator | pending |
| R-PUB-05 | OTP gate if D8 approved | Request without verified OTP rejected; OTP reuse rejected | pending |
| R-BK-01 | Lifecycle matches spec section 3 | Each allowed transition succeeds; each disallowed transition returns 409 | pending |
| R-BK-02 | Server computes end time | Client sends different end; stored end equals service duration | pending |
| R-BK-03 | No double booking | Concurrent requests for one slot: only allowed count succeeds (real PostgreSQL) | pending |
| R-BK-04 | Pending expires and frees slot | Create pending, pass expiry, job runs, slot free | pending |
| R-BK-05 | Atomic reschedule | Reschedule into full slot fails; original stays confirmed | pending |
| R-BK-06 | Cancel/reschedule cutoff and token | Cancel after cutoff returns 409; no token returns 401/403 | pending |
| R-BK-07 | Staff direct confirmed booking | Reception creates booking; status `confirmed`; audit row written | pending |
| R-STF-01 | No public staff registration | Registration endpoint does not exist or returns 403/404 | pending |
| R-STF-02 | Roles enforced server-side | Reception calls admin endpoint directly: 403 | pending |
| R-STF-03 | Audit written, no secrets | Edit patient; audit row exists; no password, token, or note text | pending |
| R-STF-04 | Paginated staff lists | List returns page size cap; filters by status and date work | pending |
| R-PAT-01 | Search paginated; near-matches flagged | Search returns capped page; same-phone patients flagged, not merged | pending |
| R-DAT-01 | No hard delete without policy | Delete endpoints for patient, appointment, image absent or 403 | pending |
| R-SEC-01 | Hashed passwords, cookie flags | Stored hash is Argon2id; cookie HttpOnly, SameSite set, Secure in production | pending |
| R-AI-01 | Demo labeled as demo | Chat and smile show demo label until Phase 8 acceptance | pending |
| R-AI-02 | Chat limited to approved content | Medical or out-of-scope question gets safe fallback, no diagnosis | pending |
| R-AI-03 | Smile is simulation | Smile result shows simulation label, no clinical claim | pending |
| Expiry job | Idempotent expiry | Run job twice after expiry: booking expired once, no error | pending |

## Gate for Package 2
- Every OWNER DECISION in product-spec.md is answered, or still listed as blocking.
- No ASSUMPTION is treated as a fact in code, tests, or UI copy.
