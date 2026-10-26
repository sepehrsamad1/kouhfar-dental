# Task 10 - Authorization and audit controls

## Approved role matrix

The owner approved the proposed Task 02 matrix for Task 10.

| Capability | Administrator | Receptionist | Practitioner |
|---|---|---|---|
| Manage staff users/roles | Allow | Deny | Deny |
| Services/practitioners/schedule | Manage | Read | Read |
| Patient records | All | All | Own patients only |
| Edit patient records | Allow | Allow | Own patients only |
| Archive patients | Allow | Deny | Deny |
| Create/confirm/reschedule/cancel bookings | Allow | Allow | Deny |
| Mark completed/no-show | Allow | Allow | Own only |
| Audit log | Allow | Deny | Deny |
| Upload/delete images | Allow with consent | Deny | Deny |
| Sensitive content/AI/configuration | Allow | Deny | Deny |

Practitioner ownership is server-derived through an explicit practitioners.staff_user_id relationship; object IDs alone do not establish scope.

## Authorization implementation

Laravel Policies are registered for patients, appointments, services, practitioners, resources, working hours, schedule exceptions, content, media, and AI requests. Configuration, staff management, and audit-log access use explicit Gates. Access is deny-by-default unless a policy or gate allows it.

## Audit controls

AuditLogger records actor, action, target, UTC timestamp, request ID, and only an allowlisted set of minimal metadata fields. Patient names, normalized mobile numbers, passwords, and other unneeded personal data are filtered out before persistence.

## Verification

Task 10 verification is pending CI. The feature test suite covers administrator/reception/practitioner allow/deny combinations, practitioner patient and appointment scope, direct endpoint IDOR-style denial, protected sensitive resources, and minimal audit metadata.
