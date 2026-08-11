# ADR-001: Laravel Monolith With Blade

Status: Accepted  
Date: 2026-08-09

## Context

Lumina Beauty is a thesis/portfolio-scale e-commerce application with public storefront, custom auth, cart/checkout, order management, and admin CRUD/reporting. The current repository implements these features in Laravel controllers, Eloquent models, migrations, Blade views, and static CSS/JS.

## Decision

Use a Laravel monolith with server-rendered Blade as the primary application architecture.

## Alternatives Considered

- Separate API backend and SPA frontend.
- Microservices.
- Server-rendered Laravel plus isolated future services for AI/chatbot.

## Consequences

- The app remains simple to run locally with DDEV.
- Business logic currently lives mostly in controllers and Eloquent models.
- Frontend behavior is progressive enhancement rather than SPA state management.
- Future React/FastAPI AI work must be treated as a separate roadmap phase, not assumed current architecture.

## Related Files

- `routes/web.php`
- `app/Http/Controllers`
- `app/Models`
- `resources/views`
- `public/assets/css`
- `public/assets/js`
- [../ARCHITECTURE.md](../ARCHITECTURE.md)
