# GitHub setup guide (Package 4)

Status: GUIDE ONLY. Nothing in this section has been done in GitHub. Each step needs an account owner with admin rights.

## 1. Create a private repository
1. Owner signs in to GitHub, clicks New repository.
2. Name: kouhfar-dental-clinic. Visibility: Private. No template.
3. Do not add a README or .gitignore from the GitHub UI (this scaffold already has them).

## 2. First push (from the scaffold)
1. `git init -b main`
2. `git add .` then check `git status`. Stop if any `.env`, image, dump, or key file is staged.
3. `git commit -m "chore: package 4 scaffold"`
4. `git remote add origin <private-repo-url>` then `git push -u origin main`.

## 3. Branch rules
- `main`: protected. No direct pushes. Only merges from pull requests.
- Feature branches: `feat/<package>-<short-name>`, `fix/...`, `docs/...`.
- Pull requests: at least one approving review from a team member other than the author.
- Require status checks (CI, Package 6) before merge. Require branch up to date.
- Dismiss stale approvals on new commits. No force-push to main.

## 4. Branch protection (Settings > Branches > Add rule for main)
Enable: require pull request, required approvals (1 or 2 by team choice), required status checks, block force push, block deletion, restrict who can push.
Status: NOT CONFIGURED.

## 5. Team access
Four members. Owner has admin. Others get Write, not Admin. Two-factor required for all members.

## 6. Lockfile and runtime (one-time, needs network)
1. Install Node 22 (see `.nvmrc`).
2. Run `npm install` at repo root. This creates `package-lock.json`. Commit it.
3. Verify with `npm ci` on a clean machine.
Status: NOT DONE.

## 7. Secrets
- Store CI secrets in GitHub Actions secrets, never in files.
- Rotate any key that was ever pasted into chat or a file.

## 8. Verify a new team member can start
Acceptance check: a new person follows README only, with no help, and reaches a running state. Not yet possible: `npm run dev` is not configured.
