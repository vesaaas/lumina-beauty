# Authentication And Security

Central security document for Lumina Beauty. Read with [CURRENT_STATE.md](CURRENT_STATE.md), [ARCHITECTURE.md](ARCHITECTURE.md), and [DATABASE.md](DATABASE.md).

## Current Controls

### Customer Registration

`AccountAuthController::register()` validates first name, last name, unique email, phone, password confirmation, and a strong password rule. It creates a non-admin user, logs the user in, merges guest commerce state, regenerates the session, and currently triggers the in-progress email OTP service.

### Customer Login

`POST /login` validates email/password, uses `Auth::attempt()`, throttles at `5,1`, merges guest commerce state, and regenerates the session.

### Admin Login

`GET/POST /admin/login` is separate from the account modal. `adminLogin()` validates credentials, requires `Auth::user()->is_admin`, logs failed admin attempts through `AuditLogService`, logs out non-admin attempts, regenerates session on success, and logs successful admin login.

### Single-Admin Design

Admin capability is represented by `users.is_admin`. `AdminUserSeeder` creates or updates the admin from environment variables and requires `ADMIN_PASSWORD`. This repository does not use roles/permissions. Maintain the single-admin decision unless explicitly changed. See [adr/002-single-administrator-model.md](adr/002-single-administrator-model.md).

### Password Reset

Customer password reset uses Laravel's password broker and `password_reset_tokens`. Admin users are intentionally blocked from the customer password reset flow. Reset routes are guest-only and password reset submission is throttled at `3,10`.

### Password Policy

Registration and reset require:

- minimum 8 characters
- mixed case
- numbers
- symbols
- password confirmation

### Sensitive Admin Password Confirmation

Sensitive admin actions verify the submitted current password directly with Laravel server-side validation:

- Brand deletion requires `password => current_password`.
- Category deletion requires `password => current_password`.
- Order status update requires `password => current_password`.

The admin UI uses a reusable password confirmation modal. The submitted password is injected into the original Laravel form only at submit time and is not logged.

The temporary `/admin/confirm-password` route/controller/view experiment has been removed to avoid competing password confirmation mechanisms.

### Rate Limiting

Configured in routes:

- Login: `throttle:5,1`
- Register: `throttle:3,10`
- Password reset request/update: `throttle:3,10`
- Admin login: `throttle:5,1`
- Email OTP verify: `throttle:5,1`
- Email OTP resend: `throttle:3,1`, plus a server-side 60-second cooldown based on `email_verification_otps.last_sent_at`
- Contact/about submission: `throttle:3,10`

### Session Lifecycle

Login, registration, and admin login regenerate the session. Logout invalidates the session and regenerates the CSRF token.

Admin responses include no-store/no-cache headers through `NoCacheAuthenticatedPages` so browser history/back-button restores must revalidate after logout. Server-side `auth` and `admin` middleware remain authoritative for every admin route.

### Security Headers

`SecurityHeaders` appends:

- `X-Frame-Options: SAMEORIGIN`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- `Content-Security-Policy-Report-Only`

CSP is report-only and currently permits self, inline scripts/styles, Lucide from unpkg, Google Fonts, Unsplash images, and data images/fonts.

### Admin Credentials

Admin credentials are environment-driven through `AdminUserSeeder`. Do not write actual admin credentials to Markdown, code comments, logs, tests, or commits.

### Audit Logging

`AuditLogService` writes selected actions to `audit_logs`, including admin login, failed admin login, product/category/brand changes, brand/category deletion, and order status changes.

### Authorization

- Admin routes require `auth` and `admin` middleware.
- Cart item updates/deletes check owner attributes.
- Storefront product detail aborts inactive products.
- Checkout associates authenticated orders with the current user where available.

### CSRF

Laravel web routes and Blade forms use CSRF protection.

### Stock And Order Integrity

Cart/checkout code validates active product, stock availability, and quantity. Checkout uses a database transaction and product `lockForUpdate()` before decrementing stock.

### Contact/About Protection

Contact and about form submissions validate input, use named error bags, and are throttled at `3,10`.

They also include local honeypot and timing fields. Bot-like submissions fail validation without external services.

### Order Confirmation Privacy

The thank-you page enforces authorization:

- authenticated orders can be viewed only by the owning user
- guest orders can be viewed only by the checkout session that created the order

## Email OTP Status

IMPLEMENTED: Account email verification uses an OTP flow with:

- `email_verification_otps` table
- hashed `code_hash`
- 10-minute expiry
- attempt counter with a five-attempt limit
- registration redirect to OTP verification
- `EmailVerificationOtpMail`
- dedicated resend endpoint: `POST /email/verify/resend`, route name `verification.otp.resend`
- resend replaces the existing OTP, resets attempts to zero, resets expiry to 10 minutes, and updates `last_sent_at`
- resend is blocked until 60 seconds after `last_sent_at`; early resend does not generate a code, does not send email, and leaves the current OTP valid
- already verified users are redirected home from the OTP page and resend endpoint
- successful verification sets `email_verified_at`, deletes the OTP row, and redirects home
- expired OTPs cannot verify and are deleted
- Gmail SMTP has been manually configured and tested locally by the developer through environment variables only; no credentials are documented

The storefront is not globally protected with Laravel's `verified` middleware yet. Broader restrictions on unverified customers remain a separate policy decision.

## Known Gaps

- No Google OAuth.
- No login 2FA.
- No guest checkout email verification.
- No global storefront/email-verified access policy.
- No production security hardening/HTTPS/HSTS documentation as completed.
- CSP is Report-Only, not enforced.

## Planned Security Work

- Google OAuth login through Laravel Socialite.
- Login 2FA.
- Guest checkout email verification.
- Resource-level authorization/policy review.
- Production hardening, including HTTPS, HSTS, secrets handling, queues, monitoring, and backups.

## Secret Handling Rule

Never store or log:

- passwords
- `password_confirmation`
- reset tokens
- OTP codes
- OAuth client secrets
- 2FA secrets
- API keys
- SMTP credentials
- production secrets
