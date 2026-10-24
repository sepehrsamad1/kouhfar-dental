# Sanitization Policy (Task 05)

## Version
Trusted HTML sanitizer v1.0.0 (in-repository allow-list implementation).

## Policy
- User-entered and potentially user-controlled plain text is inserted with `textContent`.
- Trusted templates that require markup use the single `setTrustedHTML()` helper from `apps/web/js/sanitizer.js`.
- The helper parses HTML, permits only an explicit element/attribute allow-list, removes event-handler attributes, and rejects unsafe `href`/`src` protocols.
- No `eval`, `document.write`, or dynamic script insertion is used by the site modules.
- CSS `style` is allowed only because current templates use it for layout/positioning; template sources are repository-controlled.
- This policy does not make arbitrary third-party HTML trusted. New markup/data sources must be reviewed before being added to the allow-list.

## Reviewed insertion points
`apps/web/js/03-inline.js`, `04-hero-rebuild-script.js`, `05-modern-topnav-script.js`, and `06-implant-3d-script.js`.
