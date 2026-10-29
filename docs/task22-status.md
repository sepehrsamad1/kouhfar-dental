# Task 22 — Queues, Scheduler, and Notifications

Status: IMPLEMENTED — pending CI verification.

## Owner-approved policy

- SMS production provider: Kavenegar, but production activation is deferred until Task 15 completes the approved OTP/provider subscription and credential setup.
- Development/test provider: log or null; tests use an in-process fake provider and never call a real SMS service.
- Pending booking: immediate transactional notification; pending hold is 1 hour.
- Confirmed booking: immediate transactional notification with local date/time, practitioner, clinic address, and map link.
- Reminder: 24 hours before a confirmed appointment.
- Hold expired: lifecycle notification when the 1-hour hold expires.
- Cancelled/rescheduled: immediate transactional status notification.
- Quiet hours: 22:00–08:00 Asia/Tehran. Automated notifications are released at 08:01. User-initiated transactional notifications bypass quiet hours.
- If confirmation occurs less than 24 hours before the appointment, no retroactive 24-hour reminder is created.
- The clinic address/map link is sourced from the existing approved frontend contact copy; no new address was invented.

## Queue/scheduler policy

- Production queue connection: Laravel database queue, selected with QUEUE_CONNECTION=database.
- Local/test default remains sync.
- Pending expiry command: every minute.
- Due-notification dispatcher: every minute.
- Media purge command: daily at 03:15 Asia/Tehran.
- Queue jobs use bounded retries and backoff: 60s, 300s, 900s, 1800s, max 5 attempts.
- Notification jobs use a unique outbox ID and Laravel WithoutOverlapping.
- Queue payloads contain only outbox/job IDs, not patient names, phone numbers, appointment details, or medical data.
- Laravel failed-job storage is database-backed; application-level outbox failure text is deliberately generic.

## Notification architecture

NotificationOutbox is the durable idempotency boundary. A unique dedupe_key prevents duplicate lifecycle records.

Laravel's notification system uses a custom SmsChannel backed by SmsProvider:

- LogSmsProvider
- NullSmsProvider
- KavenegarSmsProvider

The provider is resolved by configuration, so the application can switch providers without changing appointment notification classes.

Kavenegar is not contacted in tests or development unless the deployment explicitly selects the kavenegar driver and supplies credentials.

## Lifecycle hooks

- Public booking creation queues booking_pending.
- Pending expiry queues hold_expired.
- Confirmation queues booking_confirmed and, when applicable, the 24-hour reminder.
- Cancellation queues booking_cancelled.
- Reschedule queues booking_rescheduled.
- Notification jobs re-read the appointment before sending and skip stale status notifications.

## Media retention

Task 21's approved media retention implementation is now wired to the scheduler. The media command dispatches purge jobs; the existing Task 21 eligibility rules remain authoritative.

## Operational commands

Production deployment must run Laravel's scheduler every minute and run a database-backed queue worker, for example:

- php artisan schedule:run
- php artisan queue:work database --queue=default --tries=5 --timeout=60

A managed process supervisor should keep the queue worker alive. No production command was run during Task 22.

## Deliberate omissions

- No real Kavenegar credentials or subscription activation.
- No real SMS network calls.
- No OTP implementation; Task 15 remains the owner-approved verification gate.
- No invented consent/privacy/legal policy.
- No automatic patient-record deletion beyond the already approved Task 21 media policy.
