# Content register

This register is the approval/publication boundary for content managed by the ContentItem workflow.

| Page/area | Owner | Approval state | Source | Publication status |
|---|---|---|---|---|
| Home / hero | clinic owner — placeholder — owner to confirm | placeholder — owner to confirm | Existing approved website copy in current Blade frontend | Legacy static copy; not migrated into ContentItem yet |
| Services | clinic owner — placeholder — owner to confirm | Clinical claims/pricing require explicit approval | Existing approved website copy and current service UI | Legacy/static until an approved ContentItem version is published |
| Treatment results/testimonials | clinic owner — placeholder — owner to confirm | Testimonial approval required | Existing website copy/assets | Not migrated; no new claims introduced |
| Doctor profile | clinic owner — placeholder — owner to confirm | Clinical claims require explicit approval | Existing approved website copy | Not migrated |
| FAQ | clinic owner — placeholder — owner to confirm | Clinical answers require explicit approval | Existing approved website copy | Not migrated |
| Smart Smile demo | clinic owner — placeholder — owner to confirm | Non-diagnostic demo boundary retained | Existing demo copy in frontend | Static demo; no clinical inference added |
| Contact/footer | clinic owner — placeholder — owner to confirm | Business information requires owner confirmation | Existing website copy | Not migrated |
| Knowledge-based AI content | clinic owner — placeholder — owner to confirm | Only published ContentItem versions may enter knowledge context | ContentItem workflow | No unpublished content allowed |

## Workflow

draft -> review -> published -> archived

For content whose approval scope is clinical, pricing, or testimonial, an explicit owner/clinic approval record is required before publication. The current staff authorization model uses an admin staff account for that approval action; no new owner role has been invented.

Content is versioned by (content_key, locale, version). A published version is immutable through the editor; a new version must be created and then reviewed/approved/published.

Rich HTML editing is deliberately not enabled in this task. Content is stored as structured plain text and rendered with Blade escaping. If rich text is later required, it must use a strict allow-list sanitizer and a separate approval/security review.

Existing website copy is intentionally not bulk-migrated in this task because the task forbids inventing or silently approving clinical, pricing, and testimonial content. Migration requires the owner approval state to be supplied through this workflow.
