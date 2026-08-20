# Project rules

## Local builds are prohibited

- Never run a frontend or production build in the local workspace.
- Forbidden commands include `npm run build`, `npm build`, `npx vite build`, `vite build`, and any equivalent command that compiles or bundles project assets locally.
- Builds may only be performed by the remote deployment/CI process when explicitly requested by the user.
- For local verification, use targeted tests, linters, syntax checks, and static analysis that do not build assets.
