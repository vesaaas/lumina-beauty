# AGENTS.md

Durable instructions for Codex and other coding agents working on Lumina Beauty.

## Project Identity

Lumina Beauty is an existing Laravel e-commerce application for cosmetics and beauty products. Treat it as a maintained application, not a greenfield project.

Start with [docs/INDEX.md](docs/INDEX.md) and [docs/CURRENT_STATE.md](docs/CURRENT_STATE.md).

## Source Of Truth

Use this priority when facts disagree:

1. Current repository code, migrations, routes, and tests.
2. [docs/CURRENT_STATE.md](docs/CURRENT_STATE.md) for current development status.
3. Domain-specific docs under [docs/](docs/).
4. [docs/ROADMAP.md](docs/ROADMAP.md) for planned work only.
5. Historical documentation such as [docs/TECHNICAL_DOCUMENTATION.md](docs/TECHNICAL_DOCUMENTATION.md) for background.

PLANNED FEATURES MUST NEVER BE TREATED AS IMPLEMENTED FEATURES.

## Required Reading

Before modifying a subsystem, read the relevant docs and then inspect the current source code:

- Authentication/security: [docs/AUTH_SECURITY.md](docs/AUTH_SECURITY.md), [docs/CURRENT_STATE.md](docs/CURRENT_STATE.md), [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
- Database/schema: [docs/DATABASE.md](docs/DATABASE.md), relevant ADRs in [docs/adr/](docs/adr/)
- Orders/checkout: [docs/COMMERCE_ORDERS.md](docs/COMMERCE_ORDERS.md)
- Storefront: [docs/STOREFRONT.md](docs/STOREFRONT.md)
- Admin: [docs/ADMIN_PANEL.md](docs/ADMIN_PANEL.md)
- Frontend: [docs/FRONTEND.md](docs/FRONTEND.md)
- Email/integrations: [docs/EMAIL_INTEGRATIONS.md](docs/EMAIL_INTEGRATIONS.md)
- Tests: [docs/TESTING.md](docs/TESTING.md)
- Roadmap work: [docs/ROADMAP.md](docs/ROADMAP.md)
- AI workflow: [docs/AI_DEVELOPMENT_WORKFLOW.md](docs/AI_DEVELOPMENT_WORKFLOW.md)

## Development Rules

- Preserve the existing architecture unless the requested change requires otherwise.
- Do not silently redesign working functionality.
- Follow Laravel 13 conventions.
- Server-side validation and authorization are authoritative.
- Preserve order history.
- Never reintroduce physical product deletion.
- Never allow negative inventory.
- Never expose credentials or commit `.env`.
- Never log passwords, password confirmations, reset tokens, OTP codes, OAuth secrets, 2FA secrets, or other authentication secrets.
- Maintain the single-admin architectural decision unless explicitly changed.
- Use migrations for schema changes.
- Add or update tests when changing business/security behavior.
- Run relevant tests before declaring work complete.
- Review `git diff` and `git status` before completion.
- Do not commit unrelated files.

## Common Commands

```bash
ddev artisan test
ddev artisan migrate
ddev artisan migrate:status
ddev artisan optimize:clear
ddev artisan route:list
php -l <file>
git status
git diff
```

Do not put credentials in repository documentation.

## Documentation Maintenance

When a task materially changes architecture, behavior, security, database schema, integrations, roadmap status, or development workflow, update the relevant docs in the same change. Do not update docs for trivial formatting-only changes.

Change mapping:

- Authentication/security change -> update [docs/AUTH_SECURITY.md](docs/AUTH_SECURITY.md); update [docs/CURRENT_STATE.md](docs/CURRENT_STATE.md) if milestone status changes; update [docs/ROADMAP.md](docs/ROADMAP.md) if planned work becomes completed.
- Database schema change -> update [docs/DATABASE.md](docs/DATABASE.md); update [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) if architectural; create/update an ADR if the decision is significant.
- Frontend architecture change -> update [docs/FRONTEND.md](docs/FRONTEND.md).
- Storefront/order/checkout change -> update [docs/STOREFRONT.md](docs/STOREFRONT.md) or [docs/COMMERCE_ORDERS.md](docs/COMMERCE_ORDERS.md).
- Admin change -> update [docs/ADMIN_PANEL.md](docs/ADMIN_PANEL.md).
- Email/integration change -> update [docs/EMAIL_INTEGRATIONS.md](docs/EMAIL_INTEGRATIONS.md).
- New dependency -> update [docs/TECH_STACK.md](docs/TECH_STACK.md).
- Test strategy or material coverage change -> update [docs/TESTING.md](docs/TESTING.md).
- Deployment/config workflow change -> update [docs/DEPLOYMENT_OPERATIONS.md](docs/DEPLOYMENT_OPERATIONS.md).
- Important architectural decision -> create an ADR under [docs/adr/](docs/adr/).
- Completed roadmap phase -> update [docs/ROADMAP.md](docs/ROADMAP.md) and [docs/CURRENT_STATE.md](docs/CURRENT_STATE.md).

## Prohibited Assumptions

Do not assume these are implemented unless [docs/CURRENT_STATE.md](docs/CURRENT_STATE.md) explicitly says so:

- Gmail SMTP
- Google OAuth
- email OTP
- login 2FA
- guest checkout email verification
- chatbot
- FastAPI
- OpenAI API
- real payment gateway
- production deployment
