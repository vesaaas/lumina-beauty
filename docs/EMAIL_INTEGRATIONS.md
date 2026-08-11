# Email And Integrations

Read with [AUTH_SECURITY.md](AUTH_SECURITY.md), [COMMERCE_ORDERS.md](COMMERCE_ORDERS.md), and [ROADMAP.md](ROADMAP.md).

## Current Email Behavior

Laravel mail configuration lives in `config/mail.php`. The default mailer is environment-driven through `MAIL_MAILER`, with Laravel's default fallback of `log`.

No real SMTP credentials are documented here. Do not add credentials to Markdown.

Gmail SMTP has been manually configured and tested locally by the developer through environment variables only. Credentials, email passwords, and Google App Passwords must remain outside the repository.

## Mailpit Development Flow

DDEV provides Mailpit support in local development. Use DDEV mail settings when testing local SMTP/Mailpit behavior. Mailpit is for development inspection only, not production delivery.

Useful command:

```bash
ddev describe
```

Use the Mailpit URL shown by DDEV.

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

- server-side 60-second cooldown based on `email_verification_otps.last_sent_at`
- route throttle of `throttle:3,1`

Early resend attempts do not generate a new code, do not send email, and keep the existing OTP valid.

## Planned

- Google OAuth.
- Login 2FA.
- Guest checkout email verification.

Do not configure Gmail, write SMTP credentials, or add OAuth credentials in this repository documentation.
