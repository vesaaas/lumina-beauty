# Authentication And Security

Central security document for Lumina Beauty. Read with [CURRENT_STATE.md](CURRENT_STATE.md), [ARCHITECTURE.md](ARCHITECTURE.md), and [DATABASE.md](DATABASE.md).

## Phase Status

Phase 1 - Security & Authentication is complete as of the current `feature/security-authentication` branch state. Latest verified full suite after Phase 2: 160 tests, 878 assertions, 0 failures.

## Customer Registration And Email OTP

`AccountAuthController::register()` validates first name, last name, unique email, phone, password confirmation, and a strong password rule. It creates a non-admin user, logs the user in, merges guest commerce state, regenerates the session, creates an account email OTP, and redirects to the verification flow.

Account email verification uses:

- table/model: `email_verification_otps` / `EmailVerificationOtp`
- controller: `EmailVerificationOtpController`
- service: `EmailVerificationOtpService`
- mailable: `EmailVerificationOtpMail`
- route names: `verification.otp.show`, `verification.otp.verify`, `verification.otp.resend`
- hashed six-digit code storage
- 10-minute expiry
- five-attempt limit
- 15-second resend cooldown
- resend replacement that invalidates old codes
- successful verification that sets `email_verified_at` and deletes the OTP row
- queued mail with `afterCommit()`

Expired OTPs are deleted. Early resend attempts do not generate a replacement code and do not queue email.

## Customer Password Login And Email 2FA

`POST /login` validates email/password and rejects admin users from the customer login flow. Verified customer password login starts an email 2FA challenge instead of immediately authenticating.

Customer login 2FA uses:

- table/model: `login_two_factor_challenges` / `LoginTwoFactorChallenge`
- context: `customer`
- controller: `LoginTwoFactorController`
- service: `LoginTwoFactorService`
- mailable: `LoginTwoFactorCodeMail`
- route names: `login.2fa.show`, `login.2fa.verify`, `login.2fa.resend`, `login.2fa.cancel`
- hashed six-digit code storage
- 10-minute expiry
- five-attempt limit through `LoginTwoFactorService::MAX_ATTEMPTS`
- 15-second resend cooldown
- old code invalidation on resend
- used code deletion after successful verification
- pending user/context/remember/intended state in the server-side session
- full Laravel authentication only after successful 2FA
- session regeneration after authentication
- guest cart/favorite merge after successful authentication
- queued mail with `afterCommit()`

Unverified customers with valid credentials are routed into the registration email verification flow and are not allowed through account-backed commerce routes until verified.

## Admin Login And Email 2FA

Admin login is separate from the customer account modal:

- `GET /admin/login`
- `POST /admin/login`
- `GET /admin/login/2fa`
- `POST /admin/login/2fa`
- `POST /admin/login/2fa/resend`
- `POST /admin/login/2fa/cancel`

`AccountAuthController::adminLogin()` validates credentials through Laravel's auth provider, requires `users.is_admin`, logs failed admin attempts, and starts an admin-context 2FA challenge without creating a full admin session.

Admin login 2FA uses the same `LoginTwoFactorService` and `login_two_factor_challenges` table with context `admin`. Successful admin 2FA authenticates the admin, regenerates the session, clears pending state, deletes the used challenge, logs `admin.login_2fa_success`, and logs `admin.login`.

Admin 2FA is independent of customer registration email OTP. Developer-provisioned admins are authorized by `users.is_admin` and are not required to complete the public customer email verification OTP.

## Google OAuth

Google OAuth is implemented through Laravel Socialite:

- routes: `auth.google.redirect`, `auth.google.callback`
- controller: `GoogleAuthController`
- config: `config/services.php` key `services.google`
- env variables: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`
- local callback: `https://lumina-beauty.ddev.site/auth/google/callback`

Policy: Google OAuth is treated as the primary authentication factor. A successful Google OAuth login does not require an additional Lumina email OTP or login 2FA challenge.

Implemented behavior:

- missing Google credentials fail locally with `Google login is not configured yet.`
- Socialite state protection is preserved
- verified Google email is required
- existing customer accounts can be matched by verified email
- new non-admin customer accounts can be created for verified Google emails
- matching unverified customer accounts can be marked verified when Google confirms the email
- admin accounts are blocked from customer Google OAuth
- session is regenerated after successful OAuth authentication
- guest cart/favorite state is merged after successful OAuth
- safe intended redirects are preserved and external/admin redirects are rejected
- OAuth failures/cancellations are handled without exposing secrets

Real Google client credentials must remain only in `.env` or the runtime environment.

## Password Reset

Customer password reset uses Laravel's password broker and `password_reset_tokens`. Admin users are intentionally blocked from the customer password reset flow. Password reset routes are guest-only and use named throttling. Password reset completion is audit logged without logging tokens or passwords.

## Password Policy

Registration and reset require:

- minimum 8 characters
- mixed case
- numbers
- symbols
- password confirmation

Passwords are always stored through Laravel hashing.

## Rate Limiting

Rate limiting is defined as named Laravel `RateLimiter::for(...)` entries in `AppServiceProvider` and attached through `routes/web.php`. The old broad numeric throttles were replaced because Laravel's unauthenticated default key can share an IP/domain bucket across unrelated routes.

| Limiter | Limit | Keying strategy |
| --- | ---: | --- |
| `customer-login` | 5/min | flow + SHA-256 email + IP |
| `customer-login-2fa-show` | 10/min | flow + pending context + pending user/session + IP |
| `customer-login-2fa` | 5/min | flow + pending context + pending user/session + IP |
| `customer-login-2fa-resend` | 3/min | flow + pending context + pending user/session + IP |
| `customer-login-2fa-cancel` | 10/min | flow + pending context + pending user/session + IP |
| `admin-login` | 5/min | flow + SHA-256 email + IP |
| `admin-login-2fa-show` | 10/min | flow + pending context + pending user/session + IP |
| `admin-login-2fa` | 5/min | flow + pending context + pending user/session + IP |
| `admin-login-2fa-resend` | 3/min | flow + pending context + pending user/session + IP |
| `admin-login-2fa-cancel` | 10/min | flow + pending context + pending user/session + IP |
| `registration` | 3/10 min | flow + SHA-256 email + IP |
| `registration-otp-verify` | 5/min | flow + authenticated user or guest + IP |
| `registration-otp-resend` | 3/min | flow + authenticated user or guest + IP |
| `forgot-password` | 3/10 min | flow + SHA-256 email + IP |
| `password-reset` | 3/10 min | flow + SHA-256 email + IP |
| `guest-checkout-otp-show` | 10/min | flow + OTP id/session + IP |
| `guest-checkout-otp-verify` | 5/min | flow + OTP id/session + IP |
| `guest-checkout-otp-resend` | 3/min | flow + OTP id/session + IP |
| `contact` | 3/10 min | flow + SHA-256 email + IP |
| `about` | 3/10 min | flow + SHA-256 email + IP |

Admin and customer flows do not share buckets. Login and 2FA verification do not share buckets. Verify and resend do not share buckets. Contact and about have separate buckets. Add to Cart intentionally has no auth/security throttle.

## Session Lifecycle And No-Cache

Registration, unverified-login email verification entry, 2FA challenge initiation, successful 2FA authentication, Google OAuth authentication, and admin 2FA authentication regenerate the session as appropriate. Logout clears pending login 2FA and guest checkout OTP session state, invalidates the session, and regenerates the CSRF token.

`NoCacheAuthenticatedPages` applies no-store/no-cache/private headers to authenticated/private storefront, checkout/order, OTP/2FA, and admin responses so browser history/back-button restores must revalidate after logout. Server-side auth, admin, ownership, and same-session guest checks remain authoritative.

## Security Headers

`SecurityHeaders` appends:

- `X-Frame-Options: SAMEORIGIN`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- CSP as `Content-Security-Policy-Report-Only` by default

CSP can be enforced by setting `SECURITY_CSP_ENFORCE=true`. The policy currently permits self, inline scripts/styles, Lucide from unpkg, Google Fonts, Unsplash images, and data images/fonts.

HSTS is configurable through:

- `SECURITY_HSTS_ENABLED`
- `SECURITY_HSTS_MAX_AGE`
- `SECURITY_HSTS_INCLUDE_SUBDOMAINS`
- `SECURITY_HSTS_PRELOAD`

HSTS is sent only for secure production HTTPS requests when enabled. It is not sent in normal local DDEV development.

## Authorization And IDOR Protection

- Admin routes require `auth` and `admin` middleware.
- `EnsureUserIsAdmin` aborts non-admin users with 403.
- `OrderPolicy` allows admins to view orders and authenticated customers to view only their own orders.
- `StorefrontController::thankYou()` protects authenticated order confirmations through policy and guest order confirmations through same-session checkout access.
- Authenticated cart item updates/deletes verify ownership with `ownsCartItem()`.
- Guest checkout OTP rows are bound to Laravel session ID and the pending `otp_id`.
- Customer/admin login 2FA challenge routes reject context mixing.

## Sensitive Admin Password Confirmation

Sensitive admin mutations require current password validation with Laravel's `current_password` rule:

- product creation
- product update
- category creation
- category update
- category deletion
- brand creation
- brand update
- brand deletion
- order status update

The admin UI uses a reusable password confirmation modal. Submitted passwords are not retained in old input and are sanitized from audit logs.

## Audit Logging

`AuditLogService` writes selected actions to `audit_logs` and sanitizes password, password confirmation, reset token, OTP, OAuth, 2FA, and secret-like keys before persistence.

Current logged areas include:

- customer registration completed
- customer password login 2FA challenge initiation
- failed customer login
- customer login 2FA failure/success
- customer login success
- customer logout
- registration email verification success/failure/lockout/resend
- password reset requested/completed
- admin failed password login
- admin login 2FA challenge initiation/failure/resend/success
- admin login success
- admin logout
- Google OAuth success/failure/admin-blocked
- guest checkout OTP challenge creation/failure/success/resend
- product create/update
- category create/update/delete
- brand create/update/delete
- order status update

Audit logs must never contain raw passwords, current-password confirmations, reset tokens, OTP codes, OAuth tokens, app passwords, or client secrets.

## Contact/About Protection

Contact and about form submissions validate input, use named error bags, have separate named rate limiters, and include honeypot/timing fields. Bot-like submissions fail validation without external services.

## Stock And Order Integrity

Cart/checkout code validates active product, stock availability, and quantity. Checkout wraps order creation in `DB::transaction()`, reloads products with `lockForUpdate()`, repeats stock validation, decrements stock, clears cart, and prevents negative inventory.

Products use soft deletion, force deletion is blocked, and no admin product delete route is registered. Order item snapshots preserve product/brand/category/price history.

## Email Delivery

Laravel runtime mail is configured through environment variables. `.env.example` documents Gmail SMTP placeholders:

- `MAIL_MAILER=smtp`
- `MAIL_HOST=smtp.gmail.com`
- `MAIL_PORT=587`
- `MAIL_ENCRYPTION=tls`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`

Real Gmail App Passwords and addresses must remain only in `.env` or the runtime environment. Mailpit may exist as a DDEV utility, but it is not the documented Laravel runtime transport.

## Known Limitations

- Payment gateway is not implemented.
- Product Knowledge Layer is implemented separately from this security document.
- AI chatbot, React chatbot UI, FastAPI service, OpenAI Responses API integration, embeddings/vector database, and image analysis are planned future work and are not implemented.
- Production deployment, backups, monitoring, and CI/CD are not documented as complete.

## Secret Handling Rule

Never store or log:

- passwords
- `password_confirmation`
- reset tokens
- OTP codes
- OAuth client secrets
- OAuth tokens
- 2FA secrets
- API keys
- SMTP credentials
- Google App Passwords
- production secrets
