# Email And Integrations

Read with [AUTH_SECURITY.md](AUTH_SECURITY.md), [COMMERCE_ORDERS.md](COMMERCE_ORDERS.md), and [ROADMAP.md](ROADMAP.md).

## Current Email Behavior

Laravel mail configuration lives in `config/mail.php`. The default mailer is environment-driven through `MAIL_MAILER`, with Laravel's default fallback of `log`.

Account verification OTP, login 2FA, and guest checkout OTP mailables implement Laravel queueing and are queued after their database challenge rows are persisted. This avoids making the browser wait on Gmail SMTP during login/register/resend requests when a queue worker is running.

No real SMTP credentials are documented here. Do not add credentials to Markdown.

Gmail SMTP has been manually configured and tested locally by the developer through environment variables only. Credentials, email passwords, and Google App Passwords must remain outside the repository.

## Mailpit Development Flow

DDEV provides Mailpit support in local development. Use DDEV mail settings when testing local SMTP/Mailpit behavior. Mailpit is for development inspection only, not production delivery.

Useful command:

```bash
ddev describe
```

Use the Mailpit URL shown by DDEV.

Real delivery diagnostic on 2026-08-12 found the active local Laravel mailer using SMTP to DDEV Mailpit at `127.0.0.1:1025`. Queued admin 2FA mail and a direct Laravel mail diagnostic were accepted by the active transport and appeared in Mailpit, which means Gmail SMTP was not contacted and external Hotmail/Outlook delivery could not occur from that runtime configuration.

When testing real Gmail delivery instead of Mailpit, the developer must manually set runtime mail environment variables to Gmail SMTP values outside source control, clear cached Laravel configuration, restart queue workers, and start a fresh worker. Do not document the credential values.

## Password Reset

Customer password reset uses Laravel's notification/password broker flow. Admin users are intentionally blocked from the customer password reset route.

## Order Emails

Mailable: `app/Mail/OrderStatusNotification.php`  
View: `resources/views/emails/orders/status.blade.php`

Current notifications:

- `pending`: sent after checkout.
- `processing`: sent when admin updates order to processing.
- `completed`: sent when admin updates order to completed.

No dedicated cancelled email is currently implemented.

## Contact/About Email

Mailable: `app/Mail/StorefrontPageMessage.php`  
View: `resources/views/emails/storefront-page-message.blade.php`

About/contact messages are sent to `ADMIN_EMAIL` or `mail.from.address` fallback. POST routes are throttled.

## Email OTP

IMPLEMENTED for account email verification:

- Mailable: `app/Mail/EmailVerificationOtpMail.php`
- Markdown view: `resources/views/mail/email-verification-otp.blade.php`
- Service: `app/Services/EmailVerificationOtpService.php`
- Model/table: `EmailVerificationOtp` / `email_verification_otps`
- Resend route: `POST /email/verify/resend`, route name `verification.otp.resend`

The flow sends a six-digit code and stores only a hash. OTPs expire after 10 minutes, allow at most five incorrect attempts, and are deleted after successful verification. An allowed resend replaces the previous OTP, resets attempts and expiry, updates `last_sent_at`, and makes the previous code invalid.

Resend protection has two layers:

- server-side 15-second cooldown based on `email_verification_otps.last_sent_at`
- route throttle of `throttle:3,1`

Early resend attempts do not generate a new code, do not queue email, and keep the existing OTP valid.

## Login 2FA Email

IMPLEMENTED:

- Mailable: `app/Mail/LoginTwoFactorCodeMail.php`
- Markdown view: `resources/views/mail/login-two-factor-code.blade.php`
- Service: `app/Services/LoginTwoFactorService.php`
- Model/table: `LoginTwoFactorChallenge` / `login_two_factor_challenges`

Customer and admin password logins queue a six-digit security code after valid credentials but before Laravel authentication. Codes are hashed at rest, expire after 10 minutes, allow five attempts, support 15-second resend cooldown, and are deleted after successful verification.

## Guest Checkout OTP Email

IMPLEMENTED:

- Mailable: `app/Mail/GuestCheckoutOtpMail.php`
- Markdown view: `resources/views/mail/guest-checkout-otp.blade.php`
- Service: `app/Services/GuestCheckoutOtpService.php`
- Model/table: `GuestCheckoutOtp` / `guest_checkout_otps`

Guest orders are not created until the checkout email OTP succeeds. Resend replaces the previous code and does not queue mail during cooldown.

## Queue Worker Requirement

When `QUEUE_CONNECTION` is asynchronous, such as the default database queue, queued OTP/2FA emails require a running Laravel worker:

```bash
php artisan queue:work
```

In DDEV development, run:

```bash
ddev artisan queue:work
```

If no worker is running, OTP/2FA challenge rows are still created securely, but the email jobs remain pending in the queue until a worker processes them. Tests use `Mail::fake()` and `QUEUE_CONNECTION=sync`, so automated tests do not send real Gmail mail.

Queue workers are long-lived processes. After mail configuration changes or deployment, run:

```bash
ddev artisan optimize:clear
ddev artisan queue:restart
ddev artisan queue:work
```

In production, use the equivalent supervised worker restart. `optimize:clear` alone does not reload an already-running worker process.

## Google OAuth

IMPLEMENTED application-side through Laravel Socialite. Live use requires manual Google Cloud OAuth credentials in environment variables:

- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI`

Do not put real values in Markdown or source control.

## Planned

- Real Google OAuth credential activation by the developer.

Do not configure Gmail, write SMTP credentials, or add OAuth credentials in this repository documentation.
