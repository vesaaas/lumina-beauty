# Testing

Read with [DEVELOPMENT_WORKFLOW.md](DEVELOPMENT_WORKFLOW.md) and the subsystem docs for the area being changed.

## Test Configuration

`phpunit.xml` configures:

- `APP_ENV=testing`
- SQLite in-memory database
- `MAIL_MAILER=array`
- `QUEUE_CONNECTION=sync`
- `SESSION_DRIVER=array`
- low bcrypt rounds for tests

Most meaningful feature tests use `RefreshDatabase`.

## Current Test Files

### `tests/Feature/AccountAuthTest.php`

Covers customer registration phone storage, strong password rules, password old-input protection, password reset delivery/completion logging, and admin password reset blocking from the customer flow.

### `tests/Feature/EmailVerificationOtpTest.php`

Covers registration OTP row creation, queued `EmailVerificationOtpMail` with `afterCommit()`, modal rendering, hashed storage, expiry, successful verification, incorrect attempts, five-attempt lockout, resend replacement, old-code invalidation, cooldown behavior, verified-user redirects, and unauthenticated access blocking.

### `tests/Feature/LoginTwoFactorTest.php`

Covers customer/admin email login 2FA, including no immediate authentication after password entry, queued `LoginTwoFactorCodeMail` with `afterCommit()`, challenge rendering, correct code success, wrong/expired/max-attempt failures, resend cooldown, old-code invalidation, used-code deletion, pending state clearing/cancellation, safe intended redirects, admin/customer separation, admin dashboard access only after 2FA, admin logout invalidation, and the rule that developer-provisioned admins do not need public registration OTP before admin login 2FA.

### `tests/Feature/GoogleOAuthTest.php`

Covers Socialite redirect/callback behavior, missing Google credential graceful failure, configured redirect containing non-empty `client_id`, account modal Google button, verified email matching, new customer creation, no additional Lumina 2FA challenge after OAuth, admin account rejection, unverified Google email rejection, safe intended redirects, and invalid OAuth state handling.

### `tests/Feature/GuestCheckoutOtpTest.php`

Covers guest checkout OTP before order creation, queued `GuestCheckoutOtpMail` with `afterCommit()`, successful order creation after OTP, wrong/expired code rejection, resend cooldown and old-code invalidation, cross-guest verification rejection, session ID binding, and resend protection from another session.

### `tests/Feature/AdminSecurityTest.php`

Covers unauthenticated admin redirects, authenticated admin dashboard access, admin logout/no-cache behavior, current-password re-authentication for brand/category/product create/update/delete and order status changes, deletion blocking when products exist, order status transition rules, order status email behavior, audit logs, and audit secret sanitization.

### `tests/Feature/CheckoutTest.php`

Covers authenticated checkout persistence, pending order mail, out-of-stock cart rejection, checkout stock rejection, missing admin product delete route, product soft deletion preserving order item history, product force-delete blocking, and processing/completed status emails.

### `tests/Feature/SecurityRegressionTest.php`

Covers security headers, configurable CSP enforcement, production HTTPS-only HSTS, key auth/message throttling, OTP/2FA route throttling, admin login not consuming admin 2FA quota, two wrong admin 2FA attempts not prematurely triggering 429, customer login not consuming customer 2FA quota, admin/customer 2FA bucket isolation, verify/resend bucket isolation, Add to Cart not being throttled by auth limiters, and contact/about first legitimate submissions not being affected by auth throttling.

### `tests/Feature/StorefrontSecurityTest.php`

Covers own-order confirmation access, cross-customer order denial, admin `OrderPolicy` access to customer order confirmation, logout protection/no-cache, legitimate guest same-session thank-you access, random guest denial, contact/about honeypot rejection, timing rejection, and legitimate contact/about submissions.

### `tests/Feature/AdminUserSeederTest.php`

Covers developer admin provisioning through `AdminUserSeeder`, including creating an admin from configured environment values, updating an existing admin while preserving `is_admin`, and refusing to silently promote a non-admin user that already owns the configured admin email.

### `tests/Feature/ProductFilterTest.php`

Covers combined product metadata filtering after catalog seeding.

### `tests/Feature/ProductKnowledgeLayerTest.php`

Covers Product Knowledge filtering by structured metadata, null versus known-empty JSON semantics, nullable factual attributes including AM/PM suitability, deterministic recommendation matching and ordering, active sale price tie-breaking, more-than-50-candidate recommendation/routine regressions, customer-safe active-only comparison, skincare routine ordering and eligibility, admin validation/persistence, admin clear-all checkbox behavior, invalid internal vocabulary rejection, and existing storefront filter regression.

### `tests/Feature/ExampleTest.php`

Default feature smoke test for home page response. Uses seeded database.

### `tests/Unit/ExampleTest.php`

Default placeholder unit test.

## Commands

Full suite:

```bash
ddev artisan test
```

Without DDEV, if local dependencies/environment are available:

```bash
php artisan test
```

Targeted examples:

```bash
ddev artisan test tests/Feature/CheckoutTest.php
ddev artisan test tests/Feature/AccountAuthTest.php
ddev artisan test --filter=SecurityRegressionTest
php -l app/Http/Controllers/StorefrontController.php
```

## Mail Fakes

Tests use `Mail::fake()` for order and OTP/2FA email assertions and `Notification::fake()` for password reset notifications. OTP/2FA tests assert queued faked mailable instances only and do not depend on real Gmail SMTP.

## Current Coverage Areas

- Strong password policy and password reset behavior.
- Registration email OTP.
- Customer/admin login email 2FA.
- Google OAuth with Socialite and missing-credential readiness.
- Guest checkout OTP before order creation.
- Isolated named rate limiters.
- Session regeneration/logout and no-cache behavior.
- Security headers, CSP mode, and HSTS production guard.
- Checkout/order persistence.
- Stock rejection, row locking behavior, and negative-stock prevention.
- Product deletion protection and order history preservation.
- Order status notification mail.
- Admin current-password checks.
- Order status transition state machine.
- Order confirmation privacy and `OrderPolicy`.
- Audit logging and audit secret sanitization.
- Contact/about throttling and spam protection.
- Product metadata filtering.
- Product Knowledge filtering, null/empty semantics, deterministic recommendations, comparison, skincare routine ordering, admin validation/persistence, more-than-50-candidate regression behavior, AM/PM nullable filtering, and storefront filter regression coverage.

## Coverage Gaps

- Frontend behavior is not covered by browser tests.
- Production infrastructure behavior such as real queue supervisors, HTTPS termination, and backup/monitoring workflows is not covered by automated tests.

## Regression Rule

Any business/security bug fix should receive a regression test where reasonably practical.

## Latest Test Count

Latest verified checkpoint after Phase 2 Product Knowledge: full `ddev artisan test` suite passed with 160 tests, 878 assertions, 0 failures.
