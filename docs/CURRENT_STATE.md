# Current State

Update this file whenever a meaningful development milestone is completed.

Last reviewed: 2026-08-26
Branch reviewed: `feature/security-authentication`
Status basis: committed current implementation after completing Phase 2 Product Knowledge Layer. Phase 2 commit `4014b0c` (`Add product knowledge layer foundation`) is local and not pushed at this checkpoint.

## Runtime

- IMPLEMENTED: Laravel framework locked at `v13.25.0` in `composer.lock`; `composer.json` requires `laravel/framework` `^13.7`.
- IMPLEMENTED: PHP requirement is `^8.3`; DDEV is configured for PHP `8.4`.
- IMPLEMENTED: DDEV project `lumina-beauty`, type `laravel`, docroot `public`, nginx-fpm, MariaDB `10.11`, Node.js `22`.
- IMPLEMENTED: Testing uses PHPUnit `^12.5.12` with in-memory SQLite and `RefreshDatabase`.
- IMPLEMENTED: Default queue connection is environment-driven; `.env.example` uses `QUEUE_CONNECTION=database`.

## Frontend

- IMPLEMENTED: Server-rendered Blade application.
- IMPLEMENTED: Active styles/scripts are static files in `public/assets/css` and `public/assets/js`.
- PARTIAL: Vite and Tailwind 4 are configured, but current visible layouts link public static CSS/JS directly instead of Vite-built assets.
- IMPLEMENTED: Lucide icons load from `https://unpkg.com`; Google Fonts and Unsplash image URLs are used.
- IMPLEMENTED: Account modal includes email/password login, registration, and `Continue with Google`.

## Implemented Major Features

- IMPLEMENTED: Public storefront home page, products, categories, brands, sales, hot trends, product details, filters, and search.
- IMPLEMENTED: Guest favorites and cart using session/session owner state.
- IMPLEMENTED: Authenticated favorites and cart using database rows.
- IMPLEMENTED: Guest-to-user cart/favorite merge on registration, successful customer login 2FA, and successful Google OAuth.
- IMPLEMENTED: Checkout creates orders and order items, decrements stock, clears cart, and sends pending order email.
- IMPLEMENTED: Guest checkout requires a session-scoped email OTP before order creation.
- IMPLEMENTED: Order confirmation pages are protected by `OrderPolicy` for authenticated customers/admins or same-session guest checkout access.
- IMPLEMENTED: Order item snapshots store product/brand/category names and unit/line prices.
- IMPLEMENTED: Customer registration, email/password login with email login 2FA, logout, and customer password reset.
- IMPLEMENTED: Registration email verification OTP with hashed six-digit code storage, 10-minute expiry, five-attempt limit, resend replacement, cooldown, and queued mail.
- IMPLEMENTED: Customer password login email 2FA with hashed six-digit code storage, 10-minute expiry, five-attempt limit, resend replacement, cooldown, session-bound pending challenge state, and queued mail.
- IMPLEMENTED: Separate admin login email 2FA using admin context and `users.is_admin`; admin password validation does not create a full session before 2FA.
- IMPLEMENTED: Google OAuth customer login through Laravel Socialite. Google OAuth is treated as the primary authentication factor and does not require an additional Lumina email OTP/2FA challenge.
- IMPLEMENTED: Google OAuth rejects admin accounts from the customer OAuth flow, handles missing credentials gracefully, and uses environment-only Google credentials.
- IMPLEMENTED: Admin dashboard, products, categories, brands, orders, users, reports, discounts, and settings views.
- IMPLEMENTED: Product soft deletion protection and no registered admin product delete route.
- IMPLEMENTED: Audit log table/service for selected admin/security/authentication actions.
- IMPLEMENTED: Product Knowledge Layer with structured beauty-product metadata for explicit skin/hair suitability, concerns, benefits, target areas, key ingredients, routine steps, usage guidance, compatibility placeholders, and nullable factual attributes.
- IMPLEMENTED: Product Knowledge retrieval, deterministic recommendation, structured comparison, and deterministic skincare routine services. These are Laravel/MariaDB services, not AI or machine-learning features.
- IMPLEMENTED: Admin product create/edit management for Product Knowledge metadata using validated controls instead of raw JSON.
- IMPLEMENTED: Conservative demo catalog knowledge metadata for filtering, comparison, recommendations, and routine ordering.

## Implemented Security Controls

- IMPLEMENTED: CSRF protection through Laravel web middleware/forms.
- IMPLEMENTED: Strong registration/reset password rule: minimum 8 characters, mixed case, numbers, symbols, and confirmation.
- IMPLEMENTED: Customer password reset excludes admin users.
- IMPLEMENTED: Admin routes require `auth` and custom `admin` middleware.
- IMPLEMENTED: Developer admin provisioning through `AdminUserSeeder` requires configured admin email/password and refuses to silently promote a non-admin email collision.
- IMPLEMENTED: Isolated named Laravel rate limiters in `AppServiceProvider`; admin/customer/login/2FA/resend/contact/about buckets are separated.
- IMPLEMENTED: Add to Cart remains intentionally unthrottled by auth/security rate limiters.
- IMPLEMENTED: Contact/about forms have isolated throttling plus honeypot/timing spam protection.
- IMPLEMENTED: `SecurityHeaders` adds frame, content type, referrer, permissions, configurable CSP, and production HTTPS-only HSTS behavior.
- IMPLEMENTED: CSP is report-only by default and can be enforced with `SECURITY_CSP_ENFORCE=true`.
- IMPLEMENTED: HSTS is sent only when the app is in production, `SECURITY_HSTS_ENABLED=true`, and the request is HTTPS.
- IMPLEMENTED: Session defaults favor encrypted, HttpOnly, SameSite Lax cookies with secure-cookie behavior controlled by environment.
- IMPLEMENTED: Registration, login challenge starts, successful authentication, Google OAuth authentication, and logout handle session regeneration/invalidation appropriately.
- IMPLEMENTED: Authenticated/private storefront, checkout/order, OTP/2FA, and admin responses receive no-store/no-cache/private headers.
- IMPLEMENTED: `OrderPolicy` protects authenticated order visibility and allows admin view access through policy.
- IMPLEMENTED: Brand/category create, update, and delete; product create/update; and order status update require current admin password.
- IMPLEMENTED: Product purchase checks block inactive/out-of-stock products and quantities above stock.
- IMPLEMENTED: Checkout locks product rows before final stock validation/decrement and prevents negative stock.
- IMPLEMENTED: Products use soft deletes and block force delete to preserve order history.
- IMPLEMENTED: Audit logging sanitizes password, token, OTP, OAuth, and 2FA secret-like keys before persistence.

## Email And External Integrations

- IMPLEMENTED: Laravel runtime mail is configured for Gmail SMTP through environment variables in `.env`; real credentials must never be committed.
- IMPLEMENTED: `.env.example` contains safe Gmail placeholders and `QUEUE_CONNECTION=database`.
- IMPLEMENTED: OTP/2FA/security mailables are queued with `afterCommit()` after challenge persistence.
- IMPLEMENTED: Password reset uses Laravel notifications and the configured mail transport.
- IMPLEMENTED: Mailpit may still exist as a DDEV utility, but it is not the documented Laravel runtime mail transport.
- MANUAL: Real Gmail delivery requires a Google App Password stored only in `.env` and a running queue worker for queued auth/security mail.
- MANUAL: Real Google OAuth requires `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI` in `.env`.
- MANUAL: Local Google OAuth callback is `https://lumina-beauty.ddev.site/auth/google/callback`.

## Tests

- IMPLEMENTED: Feature tests cover product filtering, auth/password reset behavior, registration password policy, registration OTP, email OTP verification/resend/cooldown behavior, login 2FA, Google OAuth behavior, guest checkout OTP, checkout persistence, stock rejection, product delete protection, admin deletion/password checks, order status transitions, order status mail/audit behavior, admin post-logout redirect/no-cache behavior, order confirmation privacy, audit secret sanitization, contact/about spam protection, security headers, HSTS behavior, isolated rate limiter regression cases, and Product Knowledge behavior.
- IMPLEMENTED: Product Knowledge tests cover structured filtering, null versus known-empty metadata semantics, deterministic recommendations, comparison, skincare routine ordering, admin validation/persistence, more-than-50-candidate regression cases, nullable AM/PM filtering, customer-safe comparison visibility, and existing storefront filter regression.
- IMPLEMENTED: Default feature/unit example tests remain.
- VERIFIED CHECKPOINT: Full `ddev artisan test` suite passed after Phase 2: 160 tests, 878 assertions, 0 failures.

## Active Development Phase

Phase 1 - Security & Authentication: COMPLETE.

Phase 2 - Product Knowledge Layer: COMPLETE.

Phase 3 - AI Chatbot: NEXT / NOT IMPLEMENTED.

## Immediate Next Task

Phase 3 - AI Chatbot planning and implementation. Planned architecture is React chatbot UI -> Python/FastAPI AI service -> OpenAI Responses API -> Lumina Beauty product retrieval -> structured Product Knowledge context -> assistant response.

## Known Incomplete Work

- NEXT: AI Chatbot. React chatbot UI, Python/FastAPI AI service, OpenAI Responses API integration, embeddings/vector search, chatbot routes/controllers, and external AI integrations are not implemented yet.
- PLANNED: Payment gateway.
- PLANNED: Production deployment, backups, monitoring, CI/CD, and production secret/worker supervision.
