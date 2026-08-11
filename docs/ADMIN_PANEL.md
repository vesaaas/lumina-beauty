# Admin Panel

Read with [AUTH_SECURITY.md](AUTH_SECURITY.md), [DATABASE.md](DATABASE.md), and [COMMERCE_ORDERS.md](COMMERCE_ORDERS.md).

## Admin Authentication

Admin login uses `GET /admin/login` and `POST /admin/login` through `AccountAuthController`. Admin access requires a normal authenticated user with `is_admin = true`.

Admin routes are grouped as:

- prefix: `/admin`
- route name: `admin.*`
- middleware: `auth`, `admin`

The custom `EnsureUserIsAdmin` middleware aborts non-admin users with 403.

Admin URL responses are also covered by no-cache headers so logged-out browser history cannot expose a reusable cached admin page. Unauthenticated admin requests redirect to `admin.login`.

## Single-Admin Decision

The app uses a single developer-created admin account represented by `users.is_admin`. `AdminUserSeeder` reads admin name/email/password from environment variables. Do not introduce a role/permission system unless explicitly requested and covered by a new ADR.

See [adr/002-single-administrator-model.md](adr/002-single-administrator-model.md).

## Dashboard

Route: `GET /admin/dashboard` -> `AdminController::dashboard()` -> `resources/views/admin/dashboard.blade.php`

Shows sales, orders, users, products, pending orders, sale products, monthly target progress, revenue by month, top categories, low-stock products, top-selling products, and recent customers.

## Products

Routes:

- `GET /admin/products`
- `GET /admin/products/create`
- `POST /admin/products`
- `GET /admin/products/{product}/edit`
- `PUT /admin/products/{product}`

Products can be created and updated, including image uploads. There is intentionally no `admin.products.destroy` route. Product physical deletion must not be reintroduced.

Product create/update writes audit logs.

## Categories

Routes:

- `GET /admin/categories`
- `POST /admin/categories`
- `PUT /admin/categories/{category}`
- `DELETE /admin/categories/{category}`

Category deletion requires current password, is blocked if products exist, and writes an audit log when deletion succeeds.

## Brands

Routes:

- `GET /admin/brands`
- `POST /admin/brands`
- `PUT /admin/brands/{brand}`
- `DELETE /admin/brands/{brand}`

Brand deletion requires current password, is blocked if products exist, and writes an audit log when deletion succeeds.

## Orders

Routes:

- `GET /admin/orders`
- `GET /admin/orders/{order}`
- `PATCH /admin/orders/{order}`

Order status updates require current password through the reusable admin password modal, write an audit log only for valid real changes, and send customer email when the new status is `processing` or `completed`.

Current statuses: `pending`, `processing`, `completed`, `cancelled`.

Allowed transitions:

- `pending -> processing`
- `pending -> cancelled`
- `processing -> completed`
- `processing -> cancelled`

`completed` and `cancelled` are terminal.

## Users

Route: `GET /admin/users`

Lists users with order counts and admin/customer status.

## Reports

Route: `GET /admin/reports`

Shows latest orders, total revenue, and total discounts.

## Discounts

Route: `GET /admin/discounts`

Shows products with non-null `sale_price`.

## Settings

Route: `GET /admin/settings`

Current settings page is a read-oriented admin view. Do not store credentials in templates or docs.

## Audit Logging

`AuditLogService` is called for:

- admin login success/failure
- product create/update
- category create/update/delete
- brand create/update/delete
- order status update

Audit logging sanitizes password, token, OTP, OAuth, and 2FA secret-like keys before persistence. Never include authentication secrets in audit old/new values.

## Destructive/Sensitive Actions

Current sensitive/destructive actions:

- Brand deletion: current password required.
- Category deletion: current password required.
- Order status update: current password required.
- Product physical deletion: prohibited; no admin route exists.

These actions use one reusable admin confirmation modal rather than permanent password inputs in each table row/form.
