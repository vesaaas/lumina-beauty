# Email And Integrations

Read with [AUTH_SECURITY.md](AUTH_SECURITY.md), [COMMERCE_ORDERS.md](COMMERCE_ORDERS.md), and [ROADMAP.md](ROADMAP.md).

## Current Email Behavior

Laravel mail configuration lives in `config/mail.php`. The default mailer is environment-driven through `MAIL_MAILER`, with Laravel's default fallback of `log`.

No real SMTP credentials are documented here. Do not add credentials to Markdown.

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

IN PROGRESS in the current working tree:

- Mailable: `app/Mail/EmailVerificationOtpMail.php`
- Markdown view: `resources/views/mail/email-verification-otp.blade.php`
- Service: `app/Services/EmailVerificationOtpService.php`
- Model/table: `EmailVerificationOtp` / `email_verification_otps`

The flow sends a six-digit code and stores only a hash. This is not production-complete and does not imply Gmail SMTP is configured.

## Planned

- Gmail SMTP/manual configuration by the developer in a later phase.
- Account email OTP verification completion, including resend/cooldown UX and production delivery.
- Google OAuth.
- Login 2FA.
- Guest checkout email verification.

Gmail configuration will be performed manually by the developer later. Do not configure Gmail, write SMTP credentials, or add OAuth credentials in this repository documentation.
