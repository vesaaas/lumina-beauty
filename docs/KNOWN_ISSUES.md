# Known Issues

Only verified or strongly evidenced issues belong here.

## Open

## Queue Worker Required For OTP/2FA Mail

Status: OPEN
Severity: Medium
Area: Operations/Email
Description: OTP and login 2FA mail is queued after secure challenge persistence. When using an asynchronous queue connection, email jobs require a running Laravel queue worker.
Risk: If no worker is running, OTP/2FA database challenges are created but users will not receive the email until the queue is processed.
Desired behavior: Run `ddev artisan queue:work` in local DDEV when testing real mail delivery and supervise `php artisan queue:work` in production.
Relevant files: `app/Services/EmailVerificationOtpService.php`, `app/Services/LoginTwoFactorService.php`, `app/Services/GuestCheckoutOtpService.php`, `docs/DEPLOYMENT_OPERATIONS.md`
Notes: Do not document SMTP credentials.

## Local Mail Runtime Points To Mailpit

Status: OPEN
Severity: Medium
Area: Operations/Email
Description: Real-delivery diagnostics on 2026-08-12 found the active DDEV Laravel mail configuration using SMTP to local Mailpit at `127.0.0.1:1025`. Admin 2FA mail jobs completed and were visible in Mailpit, but Gmail SMTP was not contacted, so a Hotmail/Outlook inbox would not receive them.
Risk: Authentication challenges are persisted and queued correctly, but real external inbox delivery will not occur while the runtime points at Mailpit.
Desired behavior: Manually configure the real runtime mail environment for Gmail SMTP outside source control, then run `ddev artisan optimize:clear`, `ddev artisan queue:restart`, and start a fresh `ddev artisan queue:work`.
Relevant files: `config/mail.php`, `config/queue.php`, `docs/EMAIL_INTEGRATIONS.md`, `docs/DEPLOYMENT_OPERATIONS.md`
Notes: Do not document SMTP credentials or OTP values.

## Google OAuth Credentials Not Configured

Status: OPEN  
Severity: Medium
Area: Authentication/OAuth
Description: Google OAuth application code is implemented, but real Google Cloud credentials still need to be configured manually in environment variables.
Risk: Google sign-in cannot be used live until `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI` are configured outside source control.
Desired behavior: Developer creates/configures a Google OAuth client and stores credentials only in the runtime environment.
Relevant files: `app/Http/Controllers/Auth/GoogleAuthController.php`, `config/services.php`, `.env.example`
Notes: Never document or commit real OAuth secrets.

## In Progress

No separately tracked in-progress issue beyond the current security-authentication branch work noted in [CURRENT_STATE.md](CURRENT_STATE.md).

## Resolved

## Local Admin Account Not Provisioned

Status: RESOLVED
Severity: High
Area: Authentication/Admin
Description: Earlier DDEV database inspection found zero `users` rows with `is_admin = true`, and the configured admin email did not match an existing user. Admin login correctly rejected all accounts while that database state remained.
Risk: Resolved risk was that a legitimate developer could not complete admin login or receive admin login 2FA until a developer-provisioned admin row existed.
Desired behavior: Provision the admin through the existing `AdminUserSeeder` or an equivalent controlled manual database correction, without public admin registration or automatic promotion from the login request.
Relevant files: `database/seeders/AdminUserSeeder.php`, `app/Http/Controllers/Auth/AccountAuthController.php`
Notes: Later inspection confirmed a developer-provisioned admin exists with `is_admin = true`; do not document or expose admin email/password values.

## Email OTP Flow Is Incomplete

Status: RESOLVED
Severity: Medium
Area: Authentication/Email
Description: Account email OTP verification now includes resend, 15-second cooldown, route throttling, hashed storage, expiry, attempts, verified timestamp handling, tests, and documentation.
Risk: Resolved risk was routing users into an incomplete verification flow.
Desired behavior: Keep OTP codes hashed and never log/document plaintext codes.
Relevant files: `app/Http/Controllers/Auth/EmailVerificationOtpController.php`, `app/Services/EmailVerificationOtpService.php`, `database/migrations/2026_08_08_213500_create_email_verification_otps_table.php`, `resources/views/auth/verify-email-otp.blade.php`

## Order Status Transitions Are Too Permissive

Status: RESOLVED  
Severity: High  
Area: Commerce/Admin  
Description: `AdminController::updateOrder()` now enforces one-way transitions: `pending -> processing/cancelled`, `processing -> completed/cancelled`, with `completed` and `cancelled` terminal.  
Risk: Resolved risk was inconsistent order history from status reversal.  
Desired behavior: Keep invalid transitions from updating, emailing, or logging `order.status_updated`.  
Relevant files: `app/Http/Controllers/AdminController.php`, `app/Models/Order.php`, `resources/views/admin/orders/show.blade.php`, `tests/Feature/AdminSecurityTest.php`  
Notes: Preserve server-side enforcement.

## Thank-You Page Lacks Order Privacy Guard

Status: RESOLVED  
Severity: Medium  
Area: Commerce/Security  
Description: `StorefrontController::thankYou()` now authorizes authenticated orders by owner and guest orders by same-session checkout access.  
Risk: Resolved risk was arbitrary order confirmation access by changing IDs.  
Desired behavior: Keep owner/session checks or replace only with an equally secure signed/tokenized design.  
Relevant files: `app/Http/Controllers/StorefrontController.php`, `tests/Feature/StorefrontSecurityTest.php`  
Notes: Guest access is session-bound.

## Sensitive Admin Actions Are Inconsistent

Status: RESOLVED  
Severity: Medium  
Area: Admin/Security  
Description: Category deletion, brand deletion, and order status updates all require direct Laravel `current_password` validation and use one reusable admin modal.  
Risk: Resolved risk was inconsistent destructive-action protection and UX.  
Desired behavior: Keep direct server-side password validation for sensitive admin actions.  
Relevant files: `app/Http/Controllers/AdminController.php`, `resources/views/admin/layout.blade.php`, `resources/views/admin/categories/index.blade.php`, `resources/views/admin/brands/index.blade.php`, `resources/views/admin/orders/show.blade.php`, `tests/Feature/AdminSecurityTest.php`  
Notes: Temporary `/admin/confirm-password` route/view were removed.

## Product Physical Deletion Protected

Status: RESOLVED  
Severity: High  
Area: Commerce/Data Integrity  
Description: Products use soft deletes, force delete is blocked, order item product references are restricted, and no admin product delete route is registered.  
Risk: Resolved risk was loss of historical order integrity.  
Desired behavior: Keep this protection in place.  
Relevant files: `app/Models/Product.php`, `database/migrations/2026_07_03_000001_protect_products_from_physical_deletion.php`, `tests/Feature/CheckoutTest.php`  
Notes: See [adr/003-product-soft-deletion.md](adr/003-product-soft-deletion.md).
