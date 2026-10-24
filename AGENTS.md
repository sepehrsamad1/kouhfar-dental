# AGENTS.md: rules for contributors and AI agents

1. Read docs/STATUS.md, docs/product-spec.md, and the package prompt before any change.
2. Do only the current package. No features outside its scope.
3. Keep appearance, RTL layout, typography, and working UI behavior. Do not rewrite UI or migrate to React without a documented reason and human approval.
4. State a short plan and the affected files before changing code.
5. Owner decisions (medical policy, privacy, service fees, destructive operations) are not implemented as facts. Use documented assumptions only where low-risk.
6. Never commit secrets, env files, passwords, API keys, database dumps, or real patient images. Tests and logs use synthetic data.
7. Validation and authorization run on the server. Hiding UI is not access control.
8. Write tests for every change and run them. If a test cannot run, say exactly why. Never claim success you did not see.
9. No destructive migrations, data deletion, or production release without approval.
10. New dependencies need a stated reason and a pinned, compatible version.
11. Update docs/STATUS.md, the OpenAPI contract, and affected docs in the same change.
12. Mark demo or simulated output as demo in code, UI copy, and docs.
13. Use plain ASCII punctuation in docs and UI copy where possible. No em or en dashes in chat-facing text.
