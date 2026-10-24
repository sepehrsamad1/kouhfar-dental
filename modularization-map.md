# Modularization map (Task 05, move-only split)

Source: kouhfar-dental-persian-spacing-services-refined.html, 15,772,024 bytes, SHA-256 4381695d...8117e (verified).
Method: every inline style and script moved to its own file, in the same order and loaded with plain tags (no module change, so global scope and timing stay the same). Every embedded data URI moved to assets/. Rebuilding index.html plus these files reproduces the original byte-for-byte (checked).

## Block map (old location -> new file)
| # | Old tag | New file |
|---|---|---|
| 1 | `<style>` (228 chars) | `css/01-inline.css` |
| 2 | `<script>` (391 chars) | `js/01-inline.js` |
| 3 | `<script>` (4,132,775 chars) | `js/02-inline.js` |
| 4 | `<style>` (684,501 chars) | `css/02-inline.css` |
| 5 | `<style id="hero-rebuild-style">` (1,831,307 chars) | `css/03-hero-rebuild-style.css` |
| 6 | `<style id="smart-smile-v2-style">` (10,299 chars) | `css/04-smart-smile-v2-style.css` |
| 7 | `<style id="modern-topnav-style">` (3,855 chars) | `css/05-modern-topnav-style.css` |
| 8 | `<style id="implant-3d-style">` (4,240 chars) | `css/06-implant-3d-style.css` |
| 9 | `<style id="persian-section-labels">` (678 chars) | `css/07-persian-section-labels.css` |
| 10 | `<style id="hero-icons-and-stat-typography">` (701 chars) | `css/08-hero-icons-and-stat-typography.css` |
| 11 | `<style id="service-detail-photography">` (432 chars) | `css/09-service-detail-photography.css` |
| 12 | `<style id="persian-letter-spacing-fix">` (318 chars) | `css/10-persian-letter-spacing-fix.css` |
| 13 | `<style id="services-glass-redesign">` (2,868 chars) | `css/11-services-glass-redesign.css` |
| 14 | `<style id="hero-bottom-photo-halo">` (640 chars) | `css/12-hero-bottom-photo-halo.css` |
| 15 | `<script>` (7,236,546 chars) | `js/03-inline.js` |
| 16 | `<script id="hero-rebuild-script">` (4,676 chars) | `js/04-hero-rebuild-script.js` |
| 17 | `<script id="modern-topnav-script">` (1,316 chars) | `js/05-modern-topnav-script.js` |
| 18 | `<script id="implant-3d-script">` (947,053 chars) | `js/06-implant-3d-script.js` |

## Size table
| Part | Bytes |
|---|---|
| Original single file | 15,788,045 |
| index.html | 38,472 |
| css/ (12 files) | 101,986 |
| js/ (6 files) | 73,453 |
| assets/ (27 files, decoded) | 11,023,594 |

Note: assets are decoded binary files, so total on disk is smaller than the base64 original. The page loads them as separate requests now.

## Assets
| File | Type | Bytes |
|---|---|---|
| a-001.png | image/png | 1,353,111 |
| a-002.png | image/png | 198,994 |
| a-003.jpg | image/jpeg | 733,465 |
| a-004.jpg | image/jpeg | 812,993 |
| a-005.woff2 | font/woff2 | 50,080 |
| a-006.woff2 | font/woff2 | 51,408 |
| a-007.woff2 | font/woff2 | 51,368 |
| a-008.woff2 | font/woff2 | 51,444 |
| a-009.woff2 | font/woff2 | 51,500 |
| a-010.woff2 | font/woff2 | 51,492 |
| a-011.woff2 | font/woff2 | 51,260 |
| a-012.woff2 | font/woff2 | 51,572 |
| a-013.woff2 | font/woff2 | 50,096 |
| a-014.png | image/png | 1,368,349 |
| a-015.png | image/png | 335,877 |
| a-016.png | image/png | 359,470 |
| a-017.png | image/png | 292,407 |
| a-018.png | image/png | 397,324 |
| a-019.png | image/png | 396,923 |
| a-020.png | image/png | 366,528 |
| a-021.png | image/png | 371,727 |
| a-022.png | image/png | 588,722 |
| a-023.png | image/png | 2,069,185 |
| a-024.jpg | image/jpeg | 68,552 |
| a-025.jpg | image/jpeg | 145,590 |
| a-026.png | image/png | 33,476 |
| a-027.js | text/javascript | 670,681 |

## Not done in this step (stated, not faked)
- No Vite or build: needs npm install (no network here). Build and lint not run.
- No headless browser: visual diff at 375/768/1440 not run. Screenshots still pending.
- No sanitizer change: innerHTML sinks (booking, smile, AI widget, template literals in js/*) are listed for the next pass, not changed.
- Opening index.html from file:// may break canvas use of the images (browser tainting). Serve it over http (e.g. `npx serve web`).
- The first inline script (skill:chrome wrapper) is kept as js/01-inline.js and only adds an artifact-editable class to body.
