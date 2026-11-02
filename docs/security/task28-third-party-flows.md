# Task 28 — Third-party inventory and data-flow register

## Runtime integrations

| Service / boundary | Current state | Data sent / received | Decision / control |
|---|---|---|---|
| AI text/image providers | Fake provider only in current baseline; feature flags default off outside CI. No live credential or provider endpoint configured. | Synthetic prompt/image references in tests only. Future real providers may receive user text or image references depending on capability. | No provider may be enabled until clinic approves vendor, data fields, purpose, location, retention, deletion, subprocessors, incident terms and pricing. |
| SMS provider | SMS adapter supports log/null and Kavenegar. Current example/default is non-production log; no real credential or live provider activation is evidenced. | If Kavenegar is activated, the code sends the patient's normalized mobile number as `receptor`, the SMS body, and the configured sender. Confirmed/rescheduled reminders can include appointment date/time, practitioner display name, clinic address and public map URL; other templates disclose booking state. | Keep log/null in development and tests. Kavenegar is dormant; owner approval of these exact fields, vendor terms, data location, retention and deletion is required before activation. |

| Analytics / tracking | No analytics service or analytics SDK is configured in inspected source. | No runtime analytics transfer evidenced. | Do not add an analytics provider without owner/privacy approval and minimization review. |
| Email | Laravel mail config defaults to log in `.env.example`; no live mail provider configured. | No live outbound email flow evidenced. | Review before activation. |
| Storage | Local private media disk in current config; public disk exists separately for public assets. | Media bytes are stored locally in current configuration; production object-store provider not supplied. | Production storage vendor, region, ACL, encryption, backup, deletion and access logs are unknown. |
| Google Maps short link | A fixed clinic map URL appears in confirmed/rescheduled SMS templates. | The recipient's device opens the public map link if selected; the map provider can receive ordinary browser/network metadata. No patient identifier is embedded in the URL. | Clinic map link is static. Confirm link destination and privacy notice as part of SMS content approval. |
| GitHub / GitHub Actions | Source hosting and CI. Gitleaks action is added by Task 28; PHP/Node package registries supply dependencies. | Repository source, lockfiles, synthetic test fixtures, build artifacts and CI logs. No real patient data should be sent. | CI fixtures are synthetic. Do not add production credentials or patient data to repository, actions variables, artifacts or logs. Review action versions/permissions. |
| Composer / npm registries | Build-time dependency registries. | Package names/versions and package download requests; install/build scripts execute in CI. | Lockfiles committed; `composer audit` and `npm audit` added to CI. |
| Hosting / database / backup / log vendors | Not supplied for audit. | Could receive all database, image, log and backup data after deployment. | **Unknown — deployment owner must inventory and approve before real data use.** |

## Network egress review

The inspected `config/services.php` is empty. No real AI provider is configured; the AI provider manager uses the fake adapter unless explicitly configured. The SMS manager contains a Kavenegar adapter, but no live configuration/credential was supplied. No user-controlled URL-fetch feature was found in the reviewed controllers/services. The dormant Kavenegar adapter uses Laravel's HTTP client and the API key is part of the request path. Task 28 now restricts the configured endpoint to the exact HTTPS origin `api.kavenegar.com:443` and `/v1` path, rejects userinfo/query/fragment, disables redirects, applies a bounded timeout and returns generic transport failures without chaining request exceptions that could reveal the key. Synthetic tests cover unapproved hosts (including a metadata IP), redirect attempts and exception secrecy. No live provider request was made. Provider activation still requires clinic approval of the vendor and data flow.

Therefore SSRF is **not demonstrated in the current configured runtime**, not a claim that future network integrations are inherently safe. Any future HTTP client must use fixed allow-listed destinations, strict TLS verification, bounded timeouts/response sizes, redirect restrictions, and must not accept user-controlled full URLs.

## Approval gate

No third-party service that receives patient identifiers, patient images, appointment details, clinical notes, AI prompts containing personal data, or phone numbers may be activated until the clinic has approved the exact vendor and data flow in writing.
