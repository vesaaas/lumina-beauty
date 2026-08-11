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

Covers:

- customer registration stores phone number
- weak registration password rejection
- valid strong registration password acceptance
- registration creates an email verification OTP row in the current working tree
- customer password reset notification is sent
- admin password reset is blocked from the customer flow

### `tests/Feature/EmailVerificationOtpTest.php`

Covers:

- registration creates an OTP row and sends `EmailVerificationOtpMail`
- OTP storage is hashed and not plaintext
- OTP expiry after 10 minutes
- successful verification sets `email_verified_at` and deletes the OTP
- incorrect OTP attempts increment
- five incorrect attempts block verification until resend
- resend replaces the OTP, resets attempts, resets expiry, and sends mail
- previous OTP fails after resend
- resend before 60 seconds is rejected without replacing the OTP or sending mail
- verified users cannot open the OTP page or resend
- unauthenticated users cannot access OTP endpoints

### `tests/Feature/AdminSecurityTest.php`

Covers:

- unauthenticated admin dashboard redirects to admin login
- authenticated admin dashboard access
- admin logout clears authentication before later admin access
- admin responses include no-store/no-cache headers
- brand/category deletion requires correct current password
- unused brand/category deletion succeeds and writes audit logs
- brand/category containing products cannot be deleted
- allowed order status transitions
- rejected order status reversals and terminal-state changes
- wrong password prevents order status transition
- order status emails send only after valid transitions
- valid order status transition creates audit log
- audit logs do not persist password values

### `tests/Feature/CheckoutTest.php`

Covers:

- authenticated checkout persists order/items and sends pending order mail
- out-of-stock product cannot be added to cart
- checkout rejects out-of-stock cart items
- admin product delete route is not registered
- product soft delete preserves order item product reference/snapshot
- product force delete is blocked
- processing/completed order status changes send customer email

### `tests/Feature/ProductFilterTest.php`

Covers combined product metadata filtering after catalog seeding.

### `tests/Feature/StorefrontSecurityTest.php`

Covers:

- authenticated customer can view own order confirmation
- another customer cannot view someone else's order confirmation
- legitimate guest can view newly created order confirmation in the same session
- random guest cannot view arbitrary order confirmation
- contact honeypot submission is rejected
- legitimate contact submission still sends mail

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
ddev artisan test --filter=ProductFilterTest
php -l app/Http/Controllers/StorefrontController.php
```

## Mail Fakes

Tests use `Mail::fake()` for order and OTP email assertions and `Notification::fake()` for password reset notifications. OTP tests assert against faked `EmailVerificationOtpMail` instances only and do not depend on real Gmail SMTP.

## Current Coverage Areas

- Authentication registration/password reset basics.
- Admin password reset isolation.
- Account email OTP registration, verification, resend, cooldown, expiry, attempts, and auth access behavior.
- Checkout/order persistence.
- Stock rejection.
- Product deletion protection and order history preservation.
- Order status notification mail.
- Admin sensitive action password checks.
- Order status transition state machine.
- Order confirmation privacy.
- Audit secret sanitization.
- Contact spam protection.
- Product metadata filtering.

## Coverage Gaps

- Additional route-throttling boundary tests can be added where they remain reliable.
- Frontend behavior is not covered by browser tests.

## Regression Rule

Any business/security bug fix should receive a regression test where reasonably practical.

## Latest Test Count

Full `ddev artisan test` suite: 46 tests, 209 assertions passed.
