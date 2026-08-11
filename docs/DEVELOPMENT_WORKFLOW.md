# Development Workflow

Use this workflow for normal code changes.

## Steps

1. Inspect state:

```bash
git status
```

2. Understand current status:

```bash
sed -n '1,220p' AGENTS.md
sed -n '1,220p' docs/CURRENT_STATE.md
```

3. Read subsystem docs relevant to the task.
4. Create or use a focused feature branch.
5. Inspect the existing implementation before editing.
6. Implement the smallest coherent change.
7. Run syntax checks for touched PHP files:

```bash
php -l <file>
```

8. Run relevant tests:

```bash
ddev artisan test
ddev artisan test tests/Feature/CheckoutTest.php
```

9. Manually verify UI/workflow where necessary.
10. Review diff:

```bash
git diff
git diff --stat
```

11. Update docs when required by `AGENTS.md`.
12. Make a focused commit only when explicitly instructed or when that is the agreed workflow.

## DDEV Commands

```bash
ddev start
ddev describe
ddev artisan migrate
ddev artisan migrate:status
ddev artisan optimize:clear
ddev artisan route:list
ddev artisan test
ddev npm run dev
ddev npm run build
```

## Git And File Rules

- Never blindly use `git add .` when sensitive or unrelated files may exist.
- Never commit `.env`.
- Do not commit database backups.
- Do not commit temporary or Tinker-created files.
- Review untracked files before staging.
- Keep commits focused.
- Do not revert user changes unless explicitly requested.
- Do not commit unrelated files.

## Documentation Rule

When architecture, behavior, security, schema, integrations, roadmap status, or workflow changes materially, update docs in the same change. For formatting-only code changes, documentation updates are usually unnecessary.
