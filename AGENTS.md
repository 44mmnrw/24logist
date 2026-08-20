# Project rules

## Local builds are prohibited

- Never run a frontend or production build in the local workspace.
- Forbidden commands include `npm run build`, `npm build`, `npx vite build`, `vite build`, and any equivalent command that compiles or bundles project assets locally.
- Builds may only be performed by the remote deployment/CI process when explicitly requested by the user.
- For local verification, use targeted tests, linters, syntax checks, and static analysis that do not build assets.

## Production SSH access

- Production connection settings are stored in the local, git-ignored `.env` under the `PROD_SSH_*`, `PROD_WEB_PATH`, and `PROD_APP_PATH` keys.
- Always use the identity file from `PROD_SSH_KEY` with `IdentitiesOnly=yes` for production SSH and SCP commands.
- Never print, copy, commit, or expose the private key contents.
