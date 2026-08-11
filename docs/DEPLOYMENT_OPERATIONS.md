# Deployment And Operations

Read with [TECH_STACK.md](TECH_STACK.md), [EMAIL_INTEGRATIONS.md](EMAIL_INTEGRATIONS.md), and [DEVELOPMENT_WORKFLOW.md](DEVELOPMENT_WORKFLOW.md).

## Current

### Local Development

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

### Docker

Docker is used through DDEV. Project-specific DDEV files include nginx config, optional Adminer/phpMyAdmin compose files, and generated DDEV build files.

### Database

Local database is MariaDB through DDEV. Tests use SQLite in-memory and do not represent every MySQL/MariaDB-specific behavior.

### Mailpit

DDEV provides Mailpit for development mail inspection. Gmail SMTP is not configured by this documentation.

If active local mail configuration points to `127.0.0.1:1025`, Laravel is sending to DDEV Mailpit. Queue worker `DONE` output then means the message was accepted by local Mailpit, not delivered to an external inbox.

### Queues

OTP and login 2FA emails are queued. With the database queue driver, run a queue worker in development when testing real mail delivery:

```bash
ddev artisan queue:work
```

Production must run a supervised Laravel queue worker, for example:

```bash
php artisan queue:work
```

If the worker is stopped, OTP/2FA challenge rows are still created and verification rules remain enforced, but email jobs wait in the queue until processing resumes.

Queue workers keep their booted configuration. After changing mail or queue environment values, clear Laravel cache and restart workers before testing delivery:

```bash
ddev artisan optimize:clear
ddev artisan queue:restart
ddev artisan queue:work
```

Production should use the same lifecycle through the process supervisor, for example by restarting the supervised Laravel queue worker after deployment or mail configuration changes.

### Storage

Product image uploads use Laravel's `public` disk in admin product image handling. Public storage link behavior should be verified with Laravel's normal `storage:link` workflow when moving environments.

### Environment

Do not commit `.env`. Admin seed credentials and mail credentials belong in environment variables only.

For real Gmail SMTP delivery, configure mail environment values manually outside source control. Required values include the mailer, SMTP host, port, scheme/encryption, username, app password, and a compatible from address/name. Never commit or document the real credential values.

Useful commands:

```bash
ddev artisan migrate
ddev artisan migrate:status
ddev artisan optimize:clear
ddev artisan storage:link
```

## Planned

- Production hosting.
- Production SMTP configured manually by developer.
- Backups and restore process.
- Monitoring and alerting.
- Queue worker for queued mail/jobs.
- HTTPS and HSTS.
- CI/CD.
- Production secret management.
- Production logging/retention policy.

No production deployment is currently documented as complete.
