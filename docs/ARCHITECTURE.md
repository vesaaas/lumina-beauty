# Architecture

## Current Architecture

Lumina Beauty is a traditional Laravel 13 monolithic MVC application.

```mermaid
flowchart TD
    Browser --> Routes[routes/web.php]
    Routes --> Middleware[SecurityHeaders / auth / admin]
    Middleware --> Controllers[StorefrontController / AdminController / AccountAuthController]
    Controllers --> Models[Eloquent models]
    Controllers --> Mail[Mailables]
    Models --> DB[(MariaDB/MySQL or SQLite tests)]
    Controllers --> Blade[Blade views]
    Blade --> CSS[public/assets/css]
    Blade --> JS[public/assets/js/storefront.js]
```

## Request Lifecycle

1. Browser requests a route from `routes/web.php`.
2. Laravel applies web middleware, appended `SecurityHeaders`, and route middleware such as `auth`, `guest`, `throttle`, and `admin`.
3. Slug route model binding resolves products, categories, and brands through `getRouteKeyName()`.
4. Controllers validate input, query Eloquent models, perform business operations, send mail, and return Blade views or redirects.
5. Blade renders HTML using layouts, components, static CSS, and static JS.

## Routing

All browser routes are in `routes/web.php`.

- Public storefront routes serve home, products, categories, brands, favorites, cart, checkout, content pages, sales, and hot trends.
- Custom auth routes handle account modal redirects, login, login 2FA, register, logout, password reset, admin login, admin login 2FA, Google OAuth, and email OTP verification.
- Admin routes are grouped under `/admin`, named `admin.*`, and protected by `auth` plus `admin` middleware.

## Middleware

- `app/Http/Middleware/SecurityHeaders.php` appends common security headers and CSP Report-Only.
- `app/Http/Middleware/EnsureUserIsAdmin.php` aborts non-admin users with 403.
- `bootstrap/app.php` registers middleware aliases and redirects guests trying to reach admin routes to `admin.login`.

## Controllers

- `StorefrontController` handles catalog browsing, filters, favorites, cart, checkout, order thank-you page, content pages, and storefront email messages.
- `AdminController` handles admin dashboard, product/category/brand management, orders, users, reports, discounts, settings, audit logging calls, image upload, and order status mail.
- `AccountAuthController` handles custom customer/admin password entry, registration, password reset, logout, and starts email verification/login 2FA flows.
- `EmailVerificationOtpController` handles account email verification OTP screens, verify, and resend.
- `LoginTwoFactorController` handles customer/admin login 2FA challenge screens, verify, and resend.
- `GoogleAuthController` handles customer-only Google OAuth redirect/callback.

Business logic still lives mainly in controllers. Auth and shared-view concerns use focused services where security or reuse requires them.

## Services

- `AuditLogService` writes audit events to `audit_logs`.
- `EmailVerificationOtpService` generates a random six-digit code, stores only a hash, tracks expiry/attempt metadata, and sends `EmailVerificationOtpMail`.
- `LoginTwoFactorService` manages hashed email login 2FA challenges for customer/admin password login.
- `GuestCheckoutOtpService` manages hashed guest checkout email OTP challenges before order creation.
- `GuestCommerceService` merges guest cart/favorite state into a customer account only after registration or successful customer login 2FA/OAuth.
- `StorefrontViewData` provides common storefront layout data through a view composer and storefront controller helper.

## Models And Eloquent

Models in `app/Models` represent users, catalog data, cart/favorite state, orders, order items, audit logs, and OTP rows. Products, categories, and brands use slug route keys. Products use soft deletes and block force deletion.

See [DATABASE.md](DATABASE.md) for schema and relationships.

## Blade, JavaScript, And CSS

The UI is server-rendered Blade under `resources/views`. Active CSS/JS are:

- `public/assets/css/styles.css`
- `public/assets/css/admin.css`
- `public/assets/js/storefront.js`

Vite/Tailwind files exist but are not the active linked assets in the visible layouts. See [FRONTEND.md](FRONTEND.md).

## Database

DDEV uses MariaDB 10.11. Tests use in-memory SQLite from `phpunit.xml`. Migrations are the schema source of truth.

## Mail

Mailables handle order status, storefront page messages, account email OTP verification, login 2FA codes, and guest checkout OTP codes. Current development mail can flow through Laravel mail drivers such as Mailpit-compatible SMTP, log, array, or the manually configured Gmail SMTP environment.

See [EMAIL_INTEGRATIONS.md](EMAIL_INTEGRATIONS.md).

## DDEV Infrastructure

`.ddev/config.yaml` defines a Laravel project named `lumina-beauty` with docroot `public`, nginx-fpm, PHP 8.4, MariaDB 10.11, and Node.js 22.

## Commerce State

Guests use session-backed cart data and session-owned favorites. Authenticated users use `cart_items` and `favorites` rows. On login/register, guest cart/favorite state is merged into the authenticated user's persisted state.

## Admin Boundary

Admin access is controlled by the same `users` table with an `is_admin` boolean. There is no role/permission package. The intended architecture is a single developer-created admin account.

See [ADMIN_PANEL.md](ADMIN_PANEL.md) and [adr/002-single-administrator-model.md](adr/002-single-administrator-model.md).

## Checkout Transaction Boundary

`StorefrontController::placeOrder()` wraps order creation, item creation, stock checks, product row locks, stock decrement, and cart cleanup in a database transaction. Authenticated verified customers proceed directly. Guests first complete a session-scoped checkout email OTP challenge; the pending order email is sent after the transaction completes.

## Potential Future Refactoring

Future refactoring may move checkout, cart, favorites, filtering, and admin validation into dedicated actions/services/form requests. That is not the current architecture and should not be done unless explicitly requested or required by the task.
