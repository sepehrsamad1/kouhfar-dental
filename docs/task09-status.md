# Task 9 - Staff authentication

Status: VERIFIED.

## Scope implemented
- Laravel session-based staff authentication using the existing StaffUser model and Eloquent provider.
- Argon2id is selected through Laravel's HASH_DRIVER environment configuration.
- Password hashes are hidden and are never returned by authentication responses.
- Successful login regenerates the Laravel session ID.
- Logout calls the staff guard logout, invalidates the session, and regenerates the CSRF token.
- Disabled staff accounts are rejected with the same generic authentication error as invalid credentials.
- Login attempts are throttled by a hashed email-and-IP key and sensitive identifiers are stored only as hashes.
- Staff session registry records are created and revoked on logout or timeout.
- Idle and absolute timeout enforcement is configurable but disabled until the clinic owner approves the policy values.
- Production session cookies use the SESSION_SECURE_COOKIE setting and Laravel web middleware provides CSRF protection.
- Staff routes are separated from public routes and protected by the staff guard plus server-side session freshness middleware.
- Password reset and password-change flows are deliberately omitted because no approved recovery or verification channel exists.

## Verification evidence
GitHub Actions CI run #58 (run ID 40394941109) completed successfully on the Task 9 branch.
- JavaScript lint: passed
- JavaScript typecheck: passed
- Frontend build: passed
- PHP Pint: passed
- PHPStan: passed
- PostgreSQL migrations: passed
- Laravel tests: 95 passed, 462 assertions
- Playwright Chromium installation: passed
- Browser smoke tests: passed

## Acceptance criteria
- Staff can securely authenticate: evidenced by successful staff authentication feature tests.
- Sessions regenerate/invalidate correctly: evidenced by session fixation and logout tests.
- Passwords and secrets are not exposed: evidenced by hidden password hash and authentication response tests; login attempt records contain hashes only.
- Security tests pass: all 95 Laravel tests passed in CI.

## Security/privacy
- No real credentials, patient data, phone numbers, or external verification providers were introduced.
- Authentication errors are generic and do not disclose whether a staff account exists or is disabled.
- Password reset/SMS/email recovery was not invented.
- No staff-only code is loaded by the public homepage route.

## Open questions / assumptions
- Idle timeout policy: placeholder - owner to confirm.
- Absolute timeout policy: placeholder - owner to confirm.
- Password recovery/verification channel: placeholder - owner to confirm before any reset flow is added.
- Role-specific authorization remains for later staff-panel tasks; this task establishes authentication and does not invent D12 permissions.

## Rollback notes
Revert the Task 9 branch/PR before merge if review rejects the implementation. After merge, revert the Task 9 commit range rather than running destructive database commands. The Task 9 code does not add a new migration.

## Deliberate omissions
- No password reset or password change flow.
- No OTP, SMS, email verification, or external identity provider.
- No public patient authentication.
- No staff role/permission matrix enforcement beyond authentication; that belongs to the later staff authorization task.

## Exact next step
Merge PR #10 into main after confirming the green CI run above.
