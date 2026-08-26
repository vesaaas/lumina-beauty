# Documentation Index

Navigation hub for Lumina Beauty repository documentation.

## Start Here

- [PROJECT_OVERVIEW.md](PROJECT_OVERVIEW.md): Explains the product purpose, user roles, primary flows, and current scope. Read this when you need business context without implementation detail.
- [CURRENT_STATE.md](CURRENT_STATE.md): Verified current implementation state and development status. Read this first before any coding task.
- [TECH_STACK.md](TECH_STACK.md): Lists actual runtime, backend, frontend, local environment, and test tooling. Read before dependency, build, or environment work.
- [DEVELOPMENT_WORKFLOW.md](DEVELOPMENT_WORKFLOW.md): Preferred engineering workflow and DDEV commands. Read before making code changes.
- [AI_DEVELOPMENT_WORKFLOW.md](AI_DEVELOPMENT_WORKFLOW.md): Agent-specific safety workflow. Codex sessions should read this after `AGENTS.md`.

## Architecture

- [ARCHITECTURE.md](ARCHITECTURE.md): Current Laravel monolith architecture, request lifecycle, component relationships, and future refactoring boundaries. Read before changing controllers, routes, services, or cross-cutting behavior.
- [TECHNICAL_DOCUMENTATION.md](TECHNICAL_DOCUMENTATION.md): Historical technical documentation generated from source inspection on 2026-07-25. Use for background, but verify claims against current code and newer docs.

## Application Areas

- [STOREFRONT.md](STOREFRONT.md): Public catalog, home page, search/filtering, product details, favorites, cart, account modal, and routes. Read before storefront work.
- [ADMIN_PANEL.md](ADMIN_PANEL.md): Admin authentication, dashboard, catalog management, orders, users, reports, discounts, settings, audit logging, and sensitive actions. Read before admin work.
- [COMMERCE_ORDERS.md](COMMERCE_ORDERS.md): Guest/authenticated carts, checkout, order creation, stock checks, snapshots, status lifecycle, and order privacy behavior. Read before cart, checkout, stock, or order status changes.
- [PRODUCT_KNOWLEDGE_LAYER.md](PRODUCT_KNOWLEDGE_LAYER.md): Phase 2 structured catalog knowledge schema, known-vs-unknown semantics, retrieval, deterministic recommendations, comparison, skincare routines, admin management, and future chatbot boundary.
- [EMAIL_INTEGRATIONS.md](EMAIL_INTEGRATIONS.md): Current mail behavior and planned email integrations. Read before changing mailables, password reset delivery, OTP mail, contact forms, or SMTP configuration.

## Security

- [AUTH_SECURITY.md](AUTH_SECURITY.md): Central security document for auth, admin boundaries, password reset, rate limiting, security headers, OTP status, audit logging, known gaps, and planned security work. Read before security-sensitive changes.

## Database

- [DATABASE.md](DATABASE.md): Current schema, relationships, deletion behavior, constraints, and integrity rules. Read before migrations, model relationship changes, or commerce data work.

## Frontend

- [FRONTEND.md](FRONTEND.md): Blade/static CSS/JS architecture, layouts, components, external assets, CSP implications, and Vite/Tailwind status. Read before UI or asset work.

## Testing

- [TESTING.md](TESTING.md): Current test files, covered behavior, testing database/mail behavior, commands, and gaps. Read before changing tests or business/security behavior.

## Operations

- [DEPLOYMENT_OPERATIONS.md](DEPLOYMENT_OPERATIONS.md): Current DDEV/Docker/MariaDB setup, Gmail SMTP runtime mail requirements, queue workers, Google OAuth setup, and planned production operations. Read before environment, deployment, storage, queue, or production config work.

## Roadmap

- [ROADMAP.md](ROADMAP.md): Future work phases and completion criteria. Read only for planned work; it is not evidence that features exist.
- [KNOWN_ISSUES.md](KNOWN_ISSUES.md): Verified open and resolved issues/risks. Read before choosing fixes or declaring an area complete.

## Architecture Decisions

- [adr/README.md](adr/README.md): ADR purpose and template.
- [adr/001-laravel-monolith-with-blade.md](adr/001-laravel-monolith-with-blade.md): Laravel monolith with Blade.
- [adr/002-single-administrator-model.md](adr/002-single-administrator-model.md): Single administrator model.
- [adr/003-product-soft-deletion.md](adr/003-product-soft-deletion.md): Product soft deletion instead of physical deletion.
- [adr/004-order-item-snapshots.md](adr/004-order-item-snapshots.md): Order item snapshots preserve historical commerce data.
- [adr/005-security-first-before-ai.md](adr/005-security-first-before-ai.md): Security-first modernization before AI/chatbot work.
