# UI Audit: Shell Baseline (Package 1)

Scope: audit only. Nothing was changed. No backend connected.
Source: kouhfar-dental-persian-spacing-services-refined.html (single standalone file, about 15.8 MB, images and fonts embedded as data URIs).
Method: static read of the file with base64 stripped. Not run in a browser.

## Environment limits (stated, not faked)
- No repo, README, AGENTS.md, or status file was available at audit time. Only the HTML.
- No browser in the working environment. **Baseline mobile and desktop screenshots and console-error checks were NOT performed.**
- No internet access, so external embeds (Google Maps, WhatsApp, Instagram) were not loaded.

## Status legend
Real = works with no simulated output. Demo = runs but output is canned or simulated; must not be presented as real. Static = display only. Incomplete = placeholder or dead end.

## Section inventory
| # | Section / element | Anchor | Status | Notes |
|---|---|---|---|---|
| 1 | Loader | loader | Real (cosmetic) | Timer-based; ends on load or after 1300 ms. |
| 2 | Header and burger nav | #header, #burger, #nav | Real | Mobile menu toggles aria-expanded. |
| 3 | Hero | #home | Static + CTA | CTAs open booking (data-book) or AI (data-ai). |
| 4 | Hero intro video | #heroVideoModal, #heroVideoButton | Incomplete | Modal says the video "will play in the final version". No video source. |
| 5 | Services list and detail sheet | #services, #sheet, data-service | Real (content static) | Prev/next, accordion. Content from AK_DATA.services. |
| 6 | Results (before/after) | #results, #resTitle | Static (embedded images) | Case slots from AK_DATA.cases. Patient-image risk: see open decisions. |
| 7 | Smile assessment entry | #smile, #smileStart, #smilePreview | Demo entry | Opens modal. |
| 8 | Smile assessment modal | #ssa*, data-ssa-choice | Demo | "Gallery" and "Camera" both load canned model images. **Camera does not access a camera.** Scan and findings are scripted. UI labels it as a demo analysis. |
| 9 | Doctor section | #doctor | Static | |
| 10 | "Why" pillars | #why | Static | |
| 11 | FAQ accordion | #faq | Real (static content) | |
| 12 | Booking CTA band | #booking-cta | Static + CTA | |
| 13 | Booking wizard (6 steps) | #bk, #bkNext, #bkBack | Demo | Client-side only. See booking section. |
| 14 | AI chat widget | #fab, #ai, #aiForm | Demo | Keyword match against AK_DATA.ai.intents, with fallback. Not an AI model. UI says "sample answers". |
| 15 | Contact | #contact | Real links (tel, WhatsApp, Instagram) | No contact form. |
| 16 | Map | #contact iframe | External embed | Google Maps. Needs network. |
| 17 | Footer | footer.ft | Static + links | |
| 18 | Toast | Toast | Real | 3.2 s auto-hide. |
| 19 | Global CTA routing | data-book, data-service, data-ai, data-nav | Real | |

## Booking wizard
- Steps: service, date, time, name and phone, review, confirmation.
- Validation is client-side only: name at least 3 characters; phone must match 09 followed by 9 digits.
- Dates and times come from AK_DATA.booking. The UI says they are sample dates, not from a clinic calendar.
- Submit waits 1100 ms, then shows a reference made with Math.random() in the form AK-####-YY. **No request is sent. The confirmation is simulated.** The UI itself says no real booking is recorded.
- The file has no fetch, XMLHttpRequest, localStorage, or sessionStorage calls. Name and phone are not stored or sent, and are lost on reload.

## Global data and assets
| Object | Role | Notes |
|---|---|---|
| window.AK_ASSETS | Asset config. BASE is empty. Images are inline data URIs. | Keys: ASSET_01_DOCTOR_HERO, ASSET_02_LOGO, ASSET_03 to ASSET_06 (CASE01 and CASE02 before/after). |
| const AK_DATA | Services, cases, booking days and times, FAQ, AI intents, contact rows. | Static. This is the future API seam. |
| Smile models | Gallery and camera before/after pairs and findings as data URIs. | Canned. |

## Images, fonts, external dependencies
- Fonts: Estedad, weights 100 to 900, embedded as woff2 data URIs.
- Images: all inline data URIs.
- External (need network): Google Maps iframe and link, wa.me, Instagram. No CDN scripts or stylesheets.
- console.warn("Implant renderer could not start") exists in the script. Not reproduced.

## Suspected issues vs reproduced errors
Reproduced: **none**. No browser run was possible.
Suspected (verify in a browser):
1. Camera option does not use the device camera.
2. Booking and AI success outputs are simulated.
3. Hero video button opens an empty modal.
4. Booking references use Math.random() and could collide.
5. Smile findings are fixed per model, not computed from the user's photo.
6. Custom dialogs (#bk, #ai, #sheet, #ssa) need focus-trap and keyboard checks.
7. 15.8 MB single file will be slow on mobile. Measure in a browser.

## UI element to future capability map
| Current element | Future module | Required API (proposed) |
|---|---|---|
| Booking steps | Appointment booking | GET /api/public/services, GET /api/public/availability, POST /api/public/booking-requests |
| Booking reference | Appointment record | Server-generated ID, never Math.random() in browser |
| Name and phone fields | Patient contact (privacy-sensitive) | Server validation; storage policy is an owner decision |
| AI chat | Clinic assistant | Keep keyword FAQ, or real model (medical and privacy decision) |
| Smile modal | Smile assessment | Server-side image handling; consent; clinical-policy decision |
| Smile camera option | Device capture | Consent flow; file input or getUserMedia |
| Services sheet | Service catalog | GET /api/public/services |
| Results | Case gallery | Consent-verified images from managed storage, never from the repo |
| FAQ | Content | GET content endpoint (Package 20) |
| Contact rows and map | Clinic info | Config or CMS |
| Hero video | Intro video | Hide button until a video source exists |

## Real vs demo vs incomplete (summary)
- Real: navigation, burger menu, accordions, service sheet, toast, CTA routing, loader, contact links. Client-side UI only.
- Demo: booking submission and reference, AI answers, smile assessment (both paths), hero video, booking dates and times.

## Open decisions (owner)
1. Medical policy for smile assessment and AI answers.
2. Privacy policy for booking data and any patient images.
3. Are the before/after case images real patients? If so, consent must be documented, and they must not go into Git, tests, or logs.
4. Service fees and whether any price is shown.
5. Repo location and whether this HTML stays the source of truth.

## Verification steps (manual, once a browser is available)
1. Open the HTML at 390x844 and 1440x900. Save screenshots of hero, services, smile modal, booking steps 1 to 6, and AI widget.
2. Record every console error and warning.
3. Walk through booking to step 6. Confirm the reference changes each run and no network request is made.
4. Open smile modal, choose camera. Confirm no camera permission prompt.
5. Click the hero video button. Confirm only placeholder text appears.


## Modularization map (Task 05)

The original single-file prototype was split without changing IDs/classes, Persian copy, or script execution order.

- Inline asset registry → `apps/web/data/ak-assets.js`
- Static `AK_DATA` → `apps/web/data/ak-data.js`
- Inline styles → `apps/web/css/01-inline.css` … `12-hero-bottom-photo-halo.css`
- Inline scripts → `apps/web/js/01-inline.js`, `03-inline.js`, `04-hero-rebuild-script.js`, `05-modern-topnav-script.js`, `06-implant-3d-script.js`
- Sanitization policy/helper → `apps/web/js/sanitizer.js`, `docs/sanitization-policy.md`
- Static build/dev/lint tooling → `tools/`

## Task 05 verification

- Production build: `npm run build` (static equivalent build; no framework added).
- Lint: `npm run lint` (reference, unsafe-API, and external-script checks).
- Smoke test: `node tools/smoke-test.mjs`.
- Headless visual comparison: generated at 375, 768, and 1440 CSS pixels against the original prototype; report stored under `docs/task05-visual-diff.md`.
- Sanitization: dynamic text sinks use `textContent`; trusted markup uses the single documented `setTrustedHTML()` allow-list.
