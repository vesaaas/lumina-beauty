# Authentication And Security

Central security document for Lumina Beauty. Read with [CURRENT_STATE.md](CURRENT_STATE.md), [ARCHITECTURE.md](ARCHITECTURE.md), and [DATABASE.md](DATABASE.md).

## Current Controls

### Customer Registration

`AccountAuthController::register()` validates first name, last name, unique email, phone, password confirmation, and a strong password rule. It creates a non-admin user, logs the user in, merges guest commerce state, regenerates the session, and currently triggers the in-progress email OTP service.

### Customer Login

`POST /login` validates email/password, rejects admin accounts from the customer login flow, and throttles at `5,1`. Verified customers are not fully authenticated immediately; the controller starts an email login 2FA challenge, regenerates the session, stores only pending user/context state in the session, and queues a hashed-at-rest six-digit code through `LoginTwoFactorService`.

After a successful 2FA challenge, Laravel authenticates the customer, merges guest commerce state, regenerates the session, clears pending 2FA state, and deletes the used 2FA row. Unverified customers with valid credentials are authenticated only into the email verification flow and redirected to `/email/verify`; missing or expired verification OTPs are regenerated safely.

### Admin Login

`GET/POST /admin/login` is separate from the account modal. `adminLogin()` validates credentials through Laravel's auth provider, requires `users.is_admin`, logs failed admin attempts through `AuditLogService`, and starts an admin email login 2FA challenge without creating a full admin session. Successful admin 2FA authenticates the admin, regenerates the session, clears pending state, deletes the used code, and logs `admin.login_2fa_success` plus `admin.login`.

Admin login 2FA is independent of customer registration email OTP. Developer-provisioned admins are authorized by `users.is_admin` and are not required to complete the public customer email verification OTP flow before receiving an admin login 2FA challenge.

### Single-Admin Design

Admin capability is represented by `users.is_admin`. `AdminUserSeeder` creates or updates the admin from environment variables and requires both `ADMIN_EMAIL` and `ADMIN_PASSWORD`. It refuses to promote a non-admin user that already owns the configured admin email; resolve that database record manually before rerunning the seeder. This repository does not use roles/permissions. Maintain the single-admin decision unless explicitly changed. See [adr/002-single-administrator-model.md](adr/002-single-administrator-model.md).

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
- Email OTP resend: `throttle:3,1`, plus a server-side 15-second cooldown based on `email_verification_otps.last_sent_at`
- Login 2FA verify: `throttle:5,1`
- Login 2FA resend: `throttle:3,1`, plus a server-side 15-second cooldown based on `login_two_factor_challenges.last_sent_at`
- Guest checkout OTP verify: `throttle:5,1`
- Guest checkout OTP resend: `throttle:3,1`, plus a server-side 15-second cooldown
- Contact/about submission: `throttle:3,10`

### Session Lifecycle

Registration, unverified-login email verification entry, 2FA challenge initiation, successful 2FA authentication, Google OAuth authentication, and admin 2FA authentication regenerate the session. Logout clears pending login 2FA and guest checkout OTP session state, invalidates the session, and regenerates the CSRF token.

Authenticated/private storefront responses, OTP/2FA challenge responses, checkout/cart/favorites/order routes, and admin responses include no-store/no-cache/private headers through `NoCacheAuthenticatedPages` so browser history/back-button restores must revalidate after logout. Server-side auth, admin, ownership, and same-session guest checks remain authoritative.

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
- Authenticated, unverified customers are redirected to email verification from account-backed storefront commerce routes through `customer.verified`; guests remain allowed to browse and use guest flows.
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
- resend is blocked until 15 seconds after `last_sent_at`; early resend does not generate a code, does not send email, and leaves the current OTP valid
- already verified users are redirected home from the OTP page and resend endpoint
- successful verification sets `email_verified_at`, deletes the OTP row, and redirects home
- expired OTPs cannot verify and are deleted
- Gmail SMTP has been manually configured and tested locally by the developer through environment variables only; no credentials are documented
- email delivery is queued after the OTP row is securely persisted, so HTTP responses do not wait for Gmail SMTP when an asynchronous queue worker is running

The storefront is not globally protected with Laravel's `verified` middleware yet. Broader restrictions on unverified customers remain a separate policy decision.

## Login 2FA Status

IMPLEMENTED: Customer and admin password login use email-based login 2FA:

- `login_two_factor_challenges` table
- separate customer/admin challenge contexts
- hashed six-digit code storage
- 10-minute expiry
- five-attempt limit
- 15-second resend cooldown
- route throttling
- old code invalidation on resend
- used code deletion after successful login
- full Laravel authentication only after 2FA succeeds
- pending user/context/remember/intended state stored server-side in the session
- email delivery is queued after the challenge row is securely persisted

Admin 2FA uses the same service and table but requires admin context and rechecks `users.is_admin` before authentication.

## Google OAuth Status

IMPLEMENTED application-side with Laravel Socialite. Google OAuth is customer-only, matches by verified Google email, creates non-admin customers for verified Google emails, can mark matching unverified customer accounts verified when Google confirms the email, regenerates the session, and blocks OAuth access to admin accounts.

Manual Google Cloud credentials are still required in environment variables before real Google sign-in can be used.

## Guest Checkout OTP Status

IMPLEMENTED: Guest checkout now validates checkout input, stores pending checkout attributes in the server-side session, emails a six-digit checkout OTP, and creates the order only after successful OTP verification. It does not create a customer account automatically and preserves same-session order confirmation privacy.

## Known Gaps

- Google OAuth still needs real Google Cloud credentials configured manually outside the repository.
- No global storefront/email-verified access policy.
- No production security hardening/HTTPS/HSTS documentation as completed.
- CSP is Report-Only, not enforced.

## Planned Security Work

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
