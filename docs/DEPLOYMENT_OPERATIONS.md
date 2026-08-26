# Deployment And Operations

Read with [TECH_STACK.md](TECH_STACK.md), [EMAIL_INTEGRATIONS.md](EMAIL_INTEGRATIONS.md), and [DEVELOPMENT_WORKFLOW.md](DEVELOPMENT_WORKFLOW.md).

## Current Local Development

The project uses DDEV for local development.

`.ddev/config.yaml`:

- project name: `lumina-beauty`
- type: `laravel`
- docroot: `public`
- PHP: `8.4`
- webserver: `nginx-fpm`
- database: MariaDB `10.11`
- Node.js: `22`
- Composer: `2`

Stable DDEV URLs are normally:

- `https://lumina-beauty.ddev.site`
- `http://lumina-beauty.ddev.site`

Confirm with:

```bash
ddev describe
```

## Docker And Database

Docker is used through DDEV. Local database is MariaDB through DDEV. Tests use SQLite in-memory and do not represent every MySQL/MariaDB-specific behavior.

## Runtime Mail

Laravel runtime mail is documented for Gmail SMTP, not Mailpit.

Required environment shape:

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

Real Gmail addresses and Google App Passwords belong only in `.env` or production secret storage. Do not commit them or copy them into Markdown.

Mailpit may still be available through DDEV as a local utility. It is not the documented Laravel runtime transport. If `MAIL_HOST=127.0.0.1` and `MAIL_PORT=1025`, Laravel is sending to Mailpit rather than Gmail.

## Queues

Registration OTP, login 2FA, and guest checkout OTP emails are queued. With the database queue driver, run a worker in development when testing real delivery:

```bash
ddev artisan queue:work
```

Production must run a supervised Laravel queue worker, for example:

```bash
php artisan queue:work
```

If the worker is stopped, OTP/2FA/guest-checkout challenge rows are still created and verification rules remain enforced, but email jobs wait in the queue until processing resumes.

Queue workers keep their booted configuration. After changing mail or queue environment values:

```bash
ddev artisan optimize:clear
ddev artisan queue:restart
ddev artisan queue:work
```

Production should use the same lifecycle through the process supervisor.

## Google OAuth

Google OAuth uses Laravel Socialite. Runtime environment must provide:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://lumina-beauty.ddev.site/auth/google/callback
```

Do not commit real Google credentials. Missing credentials fail gracefully in the app before redirecting to Google.

Google Cloud setup still required for live OAuth:

- Create or select a Google Cloud OAuth client.
- Add authorized redirect URI: `https://lumina-beauty.ddev.site/auth/google/callback` for local DDEV.
- Store real client ID and client secret only in `.env` or deployment secret storage.
- Run `ddev artisan optimize:clear` after changing local config.

## Security Headers

CSP is report-only by default and can be enforced with `SECURITY_CSP_ENFORCE=true`.

HSTS is production/HTTPS guarded. It is sent only when all are true:

- app environment is `production`
- request is secure HTTPS
- `SECURITY_HSTS_ENABLED=true`

Local DDEV should not send HSTS by default.

## Storage

Product image uploads use Laravel's `public` disk in admin product image handling. Public storage link behavior should be verified with Laravel's normal `storage:link` workflow when moving environments.

## Environment Rules

- Do not commit `.env`.
- Admin seed credentials belong in environment variables only.
- Gmail App Passwords belong in environment variables only.
- Google OAuth client secrets belong in environment variables only.
- Never paste secrets into Markdown, code comments, tests, logs, or commits.

Useful commands:

```bash
ddev artisan migrate
ddev artisan migrate:status
ddev artisan optimize:clear
ddev artisan storage:link
ddev artisan queue:work
ddev artisan queue:restart
```

## Planned Production Operations

- Production hosting.
- Backups and restore process.
- Monitoring and alerting.
- CI/CD.
- Production secret management.
- Production logging/retention policy.

No production deployment is currently documented as complete.
