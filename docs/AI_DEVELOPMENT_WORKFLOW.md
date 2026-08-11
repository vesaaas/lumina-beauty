# AI Development Workflow

This file explains how Codex or another coding agent should work safely on Lumina Beauty.

## Before Coding

- Read [../AGENTS.md](../AGENTS.md).
- Read [CURRENT_STATE.md](CURRENT_STATE.md).
- Read subsystem docs relevant to the task.
- Inspect actual source code before editing.
- Inspect current git state:

```bash
git status
```

- Treat roadmap docs as planned work only.

## During Coding

- Make scoped changes.
- Preserve existing architecture unless the task explicitly requires a change.
- Validate security-sensitive behavior server-side.
- Add or update tests for business/security behavior changes.
- Do not expose secrets.
- Do not assume roadmap features exist.
- Do not silently change an architectural decision documented in an ADR.
- If an ADR decision must change, create a new ADR explaining the replacement decision.

## After Coding

Run relevant checks:

```bash
php -l <file>
ddev artisan test
ddev artisan route:list
ddev artisan migrate:status
git diff
git status
```

Use route/migration checks only when relevant to the change.

Before final response:

- summarize changed files
- list tests/checks run
- list residual risks or TODOs
- update docs when required
- confirm no unrelated files were committed or modified
