# Task 11 - Staff patient management

Status: IN PROGRESS pending CI verification.

## Scope

- Staff-only patient creation for administrator and receptionist roles.
- Practitioner lookup/update limited to patients connected through their assigned appointments.
- Paginated, capped patient search.
- Iranian/Persian mobile normalization to canonical `09xxxxxxxxx`.
- Mobile values masked in list/search responses and shown only through an authorized, audited reveal action.
- Duplicate mobile detection with explicit confirmation; no automatic merge.
- Optimistic concurrency using the patient `version` column; stale writes return HTTP 409 without current patient data.
- Administrator-only archive, consistent with the approved Task 10 role matrix.
- Every patient mutation is audited with allowlisted metadata and without patient name/mobile values.

## Minimum data

This task writes only the currently required minimum fields: full name and normalized mobile. Date of birth and sensitive notes remain outside this task because their product-spec entries are still assumptions/owner-dependent.

## Routes

All routes are behind `auth:staff` and `staff.session.fresh`:

- `GET /staff/patients` - paginated/scoped lookup.
- `POST /staff/patients` - create.
- `PATCH /staff/patients/{patient}` - optimistic-concurrency update.
- `POST /staff/patients/{patient}/archive` - administrator archive.
- `GET /staff/patients/{patient}/mobile` - authorized/audited mobile reveal.

These are web staff routes, not a new public/versioned API.

## Security/privacy

- No patient data is exposed to public routes.
- List responses mask mobile numbers.
- Patient names/mobile numbers are excluded from audit metadata.
- Practitioner scope is derived server-side from appointment/practitioner relationships.
- IDs do not grant authorization.
