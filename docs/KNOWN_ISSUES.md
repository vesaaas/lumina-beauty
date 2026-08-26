# Known Issues

Only verified or strongly evidenced issues belong here.

## Open

### Queue Worker Required For Queued Auth/Security Mail

Status: OPEN
Severity: Medium
Area: Operations/Email
Description: Registration OTP, login 2FA, and guest checkout OTP mail are queued after secure challenge persistence. With `QUEUE_CONNECTION=database`, email jobs require a running Laravel queue worker.
Risk: If no worker is running, OTP/2FA database challenges are created but users will not receive the email until the queue is processed.
Desired behavior: Run `ddev artisan queue:work` in local DDEV when testing real mail delivery and supervise `php artisan queue:work` in production.
Relevant files: `app/Services/EmailVerificationOtpService.php`, `app/Services/LoginTwoFactorService.php`, `app/Services/GuestCheckoutOtpService.php`, `docs/DEPLOYMENT_OPERATIONS.md`
Notes: Do not document SMTP credentials.

### Google OAuth Credentials Are Environment-Only

Status: OPEN
Severity: Medium
Area: Authentication/OAuth
Description: Google OAuth application code is implemented and missing credentials fail gracefully, but real Google Cloud credentials must still be configured manually in environment variables for live sign-in.
Risk: Google sign-in cannot be used live until `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI` are configured outside source control.
Desired behavior: Developer creates/configures a Google OAuth client and stores credentials only in the runtime environment.
Relevant files: `app/Http/Controllers/Auth/GoogleAuthController.php`, `config/services.php`, `.env.example`
Notes: Never document or commit real OAuth secrets.

### Production Operations Not Completed

Status: OPEN
Severity: Medium
Area: Operations
Description: Production deployment, backups, monitoring, CI/CD, production secret management, and supervised queue worker operations are not documented as complete.
Risk: The application has completed Phase 1 Security & Authentication and Phase 2 Product Knowledge locally, but still needs production operations work before a live deployment.
Desired behavior: Complete production deployment planning and document the actual deployed environment when it exists.
Relevant files: `docs/DEPLOYMENT_OPERATIONS.md`, `docs/ROADMAP.md`

## In Progress

None.

## Resolved

### Shared Auth Rate-Limit Buckets

Status: RESOLVED
Severity: Medium
Area: Authentication/Rate Limiting
Description: Broad numeric `throttle:*` middleware was replaced with isolated named Laravel rate limiters for auth, OTP, contact, and about routes.
Risk: Resolved risk was unrelated unauthenticated routes consuming the same IP/domain bucket and causing premature 429 responses.
Desired behavior: Keep admin/customer/login/2FA/resend/contact/about buckets isolated. Add to Cart remains intentionally unthrottled.
Relevant files: `app/Providers/AppServiceProvider.php`, `routes/web.php`, `tests/Feature/SecurityRegressionTest.php`

### Local Mail Runtime Pointed To Mailpit

Status: RESOLVED
Severity: Medium
Area: Operations/Email
Description: Documentation and `.env.example` now define Gmail SMTP as the Laravel runtime mail target. Mailpit may still exist as a DDEV utility but is not the documented runtime transport.
Risk: Resolved risk was confusing `queue:work DONE` with real external inbox delivery while Laravel was configured for Mailpit.
Desired behavior: Real delivery testing uses Gmail SMTP values in `.env`, `ddev artisan optimize:clear`, queue worker restart, and a running worker.
Relevant files: `.env.example`, `docs/EMAIL_INTEGRATIONS.md`, `docs/DEPLOYMENT_OPERATIONS.md`

### Google OAuth Missing Client ID Error

Status: RESOLVED
Severity: Medium
Area: Authentication/OAuth
Description: `GoogleAuthController::redirect()` now checks `services.google` config and fails locally with `Google login is not configured yet.` before redirecting to Google when credentials are missing.
Risk: Resolved risk was reaching Google with an empty `client_id`.
Desired behavior: Keep missing credentials graceful and keep real credentials out of source control.
Relevant files: `app/Http/Controllers/Auth/GoogleAuthController.php`, `tests/Feature/GoogleOAuthTest.php`

### Email OTP Flow Is Incomplete

Status: RESOLVED
Severity: Medium
Area: Authentication/Email
Description: Account email OTP verification now includes resend, 15-second cooldown, isolated route throttling, hashed storage, expiry, attempts, verified timestamp handling, tests, and documentation.
Risk: Resolved risk was routing users into an incomplete verification flow.
Desired behavior: Keep OTP codes hashed and never log/document plaintext codes.
Relevant files: `app/Http/Controllers/Auth/EmailVerificationOtpController.php`, `app/Services/EmailVerificationOtpService.php`, `resources/views/auth/verify-email-otp.blade.php`

### Order Status Transitions Are Too Permissive

Status: RESOLVED
Severity: High
Area: Commerce/Admin
Description: `AdminController::updateOrder()` now enforces one-way transitions: `pending -> processing/cancelled`, `processing -> completed/cancelled`, with `completed` and `cancelled` terminal.
Risk: Resolved risk was inconsistent order history from status reversal.
Desired behavior: Keep invalid transitions from updating, emailing, or logging `order.status_updated`.
Relevant files: `app/Http/Controllers/AdminController.php`, `app/Models/Order.php`, `tests/Feature/AdminSecurityTest.php`

### Thank-You Page Lacks Order Privacy Guard

Status: RESOLVED
Severity: Medium
Area: Commerce/Security
Description: `StorefrontController::thankYou()` now authorizes authenticated orders through `OrderPolicy` and guest orders by same-session checkout access.
Risk: Resolved risk was arbitrary order confirmation access by changing IDs.
Desired behavior: Keep owner/session checks or replace only with an equally secure signed/tokenized design.
Relevant files: `app/Http/Controllers/StorefrontController.php`, `app/Policies/OrderPolicy.php`, `tests/Feature/StorefrontSecurityTest.php`

### Sensitive Admin Actions Are Inconsistent

Status: RESOLVED
Severity: Medium
Area: Admin/Security
Description: Product create/update, category create/update/delete, brand create/update/delete, and order status updates require direct Laravel `current_password` validation and use one reusable admin modal.
Risk: Resolved risk was inconsistent sensitive-action protection and UX.
Desired behavior: Keep direct server-side password validation for sensitive admin actions.
Relevant files: `app/Http/Controllers/AdminController.php`, `resources/views/admin/layout.blade.php`, `tests/Feature/AdminSecurityTest.php`

### Product Physical Deletion Protected

Status: RESOLVED
Severity: High
Area: Commerce/Data Integrity
Description: Products use soft deletes, force delete is blocked, order item product references are restricted, and no admin product delete route is registered.
Risk: Resolved risk was loss of historical order integrity.
Desired behavior: Keep this protection in place.
Relevant files: `app/Models/Product.php`, `database/migrations/2026_07_03_000001_protect_products_from_physical_deletion.php`, `tests/Feature/CheckoutTest.php`
Notes: See [adr/003-product-soft-deletion.md](adr/003-product-soft-deletion.md).
