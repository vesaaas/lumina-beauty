# Email And Integrations

Read with [AUTH_SECURITY.md](AUTH_SECURITY.md), [COMMERCE_ORDERS.md](COMMERCE_ORDERS.md), and [ROADMAP.md](ROADMAP.md).

## Current Email Behavior

Laravel mail configuration lives in `config/mail.php`. The default mailer is environment-driven through `MAIL_MAILER`.

Current documented runtime mail target is Gmail SMTP through `.env` values:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-gmail-address@gmail.com
MAIL_PASSWORD=your-google-app-password
MAIL_FROM_ADDRESS=your-gmail-address@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
QUEUE_CONNECTION=database
```

Use only a Google App Password for Gmail SMTP. Never document or commit the real Gmail address, App Password, OAuth client secret, tokens, or any other credential.

Mailpit may still exist as a DDEV local utility, but it is not the documented Laravel runtime mail transport. If Laravel is pointed at `127.0.0.1:1025`, messages are going to Mailpit, not Gmail.

## Queued Auth/Security Mail

These auth/security mailables implement Laravel queueing and are queued with `afterCommit()` after their database challenge rows are persisted:

- `EmailVerificationOtpMail`
- `LoginTwoFactorCodeMail`
- `GuestCheckoutOtpMail`

With `QUEUE_CONNECTION=database`, a worker must process jobs:

```bash
ddev artisan queue:work
```

If no worker is running, challenge rows are still created securely, but queued emails remain pending until a worker processes them.

After changing mail or queue environment values:

```bash
ddev artisan optimize:clear
ddev artisan queue:restart
ddev artisan queue:work
```

Queue workers are long-lived and must be restarted after configuration changes.

## Password Reset

Customer password reset uses Laravel's notification/password broker flow and the configured mail transport. Admin users are intentionally blocked from the customer password reset route. Password reset requested/completed events are audit logged without storing reset tokens.

## Order Emails

Mailable: `app/Mail/OrderStatusNotification.php`  
View: `resources/views/emails/orders/status.blade.php`

Current notifications:

- `pending`: sent after checkout.
- `processing`: sent when admin updates order to processing.
- `completed`: sent when admin updates order to completed.

No dedicated cancelled email is currently implemented.

Order/status mail is not part of the `afterCommit()` auth/security mail hardening unless changed in future work.

## Contact/About Email

Mailable: `app/Mail/StorefrontPageMessage.php`  
View: `resources/views/emails/storefront-page-message.blade.php`

About/contact messages are sent to `ADMIN_EMAIL` or `mail.from.address` fallback. POST routes use isolated named rate limiters and honeypot/timing spam protection.

## Registration Email OTP

IMPLEMENTED:

- Mailable: `app/Mail/EmailVerificationOtpMail.php`
- Markdown view: `resources/views/mail/email-verification-otp.blade.php`
- Service: `app/Services/EmailVerificationOtpService.php`
- Model/table: `EmailVerificationOtp` / `email_verification_otps`
- Verify route: `POST /email/verify`, route name `verification.otp.verify`
- Resend route: `POST /email/verify/resend`, route name `verification.otp.resend`

The flow sends a six-digit code and stores only a hash. OTPs expire after 10 minutes, allow at most five incorrect attempts, and are deleted after successful verification. An allowed resend replaces the previous OTP, resets attempts and expiry, updates `last_sent_at`, and makes the previous code invalid.

Resend protection has two layers:

- server-side 15-second cooldown based on `email_verification_otps.last_sent_at`
- named route limiter `registration-otp-resend`

## Login 2FA Email

IMPLEMENTED:

- Mailable: `app/Mail/LoginTwoFactorCodeMail.php`
- Markdown view: `resources/views/mail/login-two-factor-code.blade.php`
- Service: `app/Services/LoginTwoFactorService.php`
- Model/table: `LoginTwoFactorChallenge` / `login_two_factor_challenges`
- Customer verify route name: `login.2fa.verify`
- Admin verify route name: `admin.login.2fa.verify`

Customer and admin password logins queue a six-digit security code after valid credentials but before Laravel authentication. Codes are hashed at rest, expire after 10 minutes, allow five attempts, support 15-second resend cooldown, and are deleted after successful verification.

Google OAuth does not trigger this Lumina email 2FA challenge by policy.

## Guest Checkout OTP Email

IMPLEMENTED:

- Mailable: `app/Mail/GuestCheckoutOtpMail.php`
- Markdown view: `resources/views/mail/guest-checkout-otp.blade.php`
- Service: `app/Services/GuestCheckoutOtpService.php`
- Model/table: `GuestCheckoutOtp` / `guest_checkout_otps`
- Verify route: `POST /checkout/email/verify`, route name `checkout.guest.otp.verify`
- Resend route: `POST /checkout/email/verify/resend`, route name `checkout.guest.otp.resend`

Guest orders are not created until the checkout email OTP succeeds. OTP rows are bound to the Laravel session ID and pending `otp_id`. Resend replaces the previous code and does not queue mail during cooldown.

## Google OAuth

IMPLEMENTED application-side through Laravel Socialite.

Environment variables:

- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI`

Local callback:

```text
https://lumina-beauty.ddev.site/auth/google/callback
```

Missing Google credentials fail gracefully before redirecting to Google. Real Google OAuth credentials must be configured manually in `.env` or the runtime environment.

Google OAuth is customer-only. Admin accounts cannot authenticate through the customer Google OAuth flow. Successful Google OAuth does not require an additional Lumina email OTP or login 2FA challenge.

## Tests

Tests use `Mail::fake()` and `Notification::fake()` where appropriate. `phpunit.xml` uses `MAIL_MAILER=array` and `QUEUE_CONNECTION=sync`, so automated tests do not send real Gmail mail.

Latest verified full suite after Phase 2: 160 tests, 878 assertions, 0 failures.
