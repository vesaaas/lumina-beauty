# ADR-002: Single Administrator Model

Status: Accepted  
Date: 2026-08-09

## Context

The current code uses the `users` table for customers and admin identity, with an `is_admin` boolean. Admin routes are protected by `auth` and custom `admin` middleware. `AdminUserSeeder` creates or updates the admin account from environment variables.

## Decision

Maintain a single developer-created administrator model unless the project explicitly changes scope.

## Alternatives Considered

- Role/permission package.
- Multiple admin roles.
- Separate admin table/guard.

## Consequences

- Authorization remains simple and understandable.
- Admin credentials stay environment-managed.
- Multi-admin collaboration, granular permissions, and role audit workflows are out of scope until a new decision replaces this ADR.

## Related Files

- `database/migrations/2026_05_12_000001_add_is_admin_to_users_table.php`
- `database/seeders/AdminUserSeeder.php`
- `app/Http/Middleware/EnsureUserIsAdmin.php`
- `app/Http/Controllers/Auth/AccountAuthController.php`
- [../ADMIN_PANEL.md](../ADMIN_PANEL.md)
- [../AUTH_SECURITY.md](../AUTH_SECURITY.md)
