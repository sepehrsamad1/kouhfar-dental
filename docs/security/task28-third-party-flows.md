# Task 28 — Third-party inventory and data-flow register

## Runtime integrations

| Service / boundary | Current state | Data sent / received | Decision / control |
|---|---|---|---|
| AI text/image providers | Fake provider only in current baseline; feature flags default off outside CI. No live credential or provider endpoint configured. | Synthetic prompt/image references in tests only. Future real providers may receive user text or image references depending on capability. | No provider may be enabled until clinic approves vendor, data fields, purpose, location, retention, deletion, subprocessors, incident terms and pricing. |
| SMS provider | SMS adapter supports log/null and Kavenegar. Current example/default is non-production log; no real credential or live provider activation is evidenced. | Notification payload is constructed by application; exact provider payload and any phone-number disclosure must be reviewed before enabling Kavenegar. | Keep log/null in development and tests. Kavenegar is dormant; owner approval and a data-flow review are required before activation. |
| Analytics / tracking | No analytics service or analytics SDK is configured in inspected source. | No runtime analytics transfer evidenced. | Do not add an analytics provider without owner/privacy approval and minimization review. |
| Email | Laravel mail config defaults to log in `.env.example`; no live mail provider configured. | No live outbound email flow evidenced. | Review before activation. |
| Storage | Local private media disk in current config; public disk exists separately for public assets. | Media bytes are stored locally in current configuration; production object-store provider not supplied. | Production storage vendor, region, ACL, encryption, backup, deletion and access logs are unknown. |
| GitHub / GitHub Actions | Source hosting and CI. Gitleaks action is added by Task 28; PHP/Node package registries supply dependencies. | Repository source, lockfiles, synthetic test fixtures, build artifacts and CI logs. No real patient data should be sent. | CI fixtures are synthetic. Do not add production credentials or patient data to repository, actions variables, artifacts or logs. Review action versions/permissions. |
| Composer / npm registries | Build-time dependency registries. | Package names/versions and package download requests; install/build scripts execute in CI. | Lockfiles committed; `composer audit` and `npm audit` added to CI. |
| Hosting / database / backup / log vendors | Not supplied for audit. | Could receive all database, image, log and backup data after deployment. | **Unknown — deployment owner must inventory and approve before real data use.** |

## Network egress review

The inspected `config/services.php` is empty. No real AI provider is configured; the AI provider manager uses the fake adapter unless explicitly configured. The SMS manager contains a Kavenegar adapter, but no live configuration/credential was supplied. No user-controlled URL-fetch feature was found in the reviewed controllers/services.

Therefore SSRF is **not applicable to the current configured runtime**, not a claim that future network integrations are inherently safe. Any future HTTP client must use fixed allow-listed destinations, strict TLS verification, bounded timeouts/response sizes, redirect restrictions, and must not accept user-controlled full URLs.

## Approval gate

No third-party service that receives patient identifiers, patient images, appointment details, clinical notes, AI prompts containing personal data, or phone numbers may be activated until the clinic has approved the exact vendor and data flow in writing.
