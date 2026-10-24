# Resume point (as of 2026-10-23)

Prompt source of truth: Dental_Clinic_32_AI_Prompts_v2 (English, Tasks 01-32).
Note: the "Full Prompts" file is a translation of the same pack (Packages 1-32). Use v2 numbering here.

## Status
| Task | State | What is missing |
|---|---|---|
| 01 UI audit | Docs done | No browser baseline (screenshots, console log). Needs a real browser. |
| 02 Requirements | Draft done | Owner decisions D1 to D16 still open (docs/product-spec.md section 8). |
| 03 Architecture and API contract | Design done | Not validated with a linter; TS types not generated. |
| 04 Repo setup | Scaffold done | Lockfile not generated (needs npm install with network). Clean-clone check not run. GitHub settings pending owner. |
| 05 Modularize UI | VERIFIED | Modular web split, static data modules, extracted assets, lightweight build/dev/lint tooling, sanitizer policy, smoke checks, and 375/768/1440 browser comparison completed. Official Task 01 screenshots were unavailable, so comparison used the exact original HTML source. |
| 06 to 32 | NOT STARTED | |

## Next prompt to send
Task 05 is VERIFIED. Move on to Task 06. `package-lock.json` was generated locally with npm; no external runtime dependency was added.
Also run `npm install` on a machine with network to create package-lock.json and commit it (Task 04 item 3).

## Rules for resuming
- Do not redo finished tasks. Pick up at the first Not started or Pending item above.
- Record each finished task here and in docs/STATUS.md, with its commit SHA once the repo exists.
