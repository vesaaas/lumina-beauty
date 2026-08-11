# Tech Stack

## Backend

| Technology | Status | Use |
| --- | --- | --- |
| PHP `^8.3` | Active | Runtime requirement from `composer.json`; DDEV uses PHP `8.4`. |
| Laravel Framework `v13.8.0` | Active | Main web framework, routing, MVC, Eloquent, validation, auth, mail, testing helpers. |
| Laravel Tinker `^3.0` | Active dev dependency | Local interactive debugging/tooling. |
| Composer 2 | Active | PHP dependency management; DDEV config sets Composer version 2. |

## Database

| Technology | Status | Use |
| --- | --- | --- |
| MariaDB 10.11 | Active local DB | Configured in `.ddev/config.yaml`. |
| MySQL/MariaDB SQL | Active assumption | Migrations and some reporting queries target MySQL/MariaDB behavior, including `DATE_FORMAT`. |
| SQLite in-memory | Active for tests | `phpunit.xml` sets `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`. |

## Frontend

| Technology | Status | Use |
| --- | --- | --- |
| Blade | Active | Server-rendered storefront, admin, auth, components, and email views. |
| Public CSS | Active | `public/assets/css/styles.css` and `public/assets/css/admin.css` are linked directly from layouts. |
| Vanilla JavaScript | Active | `public/assets/js/storefront.js` handles progressive UI behavior. |
| Lucide | Active external asset | Loaded from `https://unpkg.com` for icons. |
| Google Fonts | Active external asset | Loaded in storefront/admin layouts. |
| Unsplash images | Active external asset | Seed data, CSS, and views reference Unsplash image URLs. |
| Vite | Partial | Configured in `vite.config.js` but not used by current visible layouts. |
| Tailwind CSS 4 | Partial | Installed/configured through Vite; active UI is mostly static CSS. |
| Vue/React | Not present | No Vue or React implementation exists in the current source. React is roadmap-only for future AI/chatbot work. |

## Local Environment

| Technology | Status | Use |
| --- | --- | --- |
| DDEV | Active | Local Laravel/Docker orchestration. |
| Docker | Active through DDEV | Containers for web/database/mail services. |
| nginx-fpm | Active | DDEV webserver type. |
| Mailpit | Active through DDEV convention | Local email inspection target when environment mail settings use DDEV SMTP. |
| Node.js 22 | Active local toolchain | DDEV node version for npm/Vite tooling. |

## Testing And QA

| Technology | Status | Use |
| --- | --- | --- |
| PHPUnit `^12.5.12` | Active | Unit and feature test runner. |
| Laravel testing helpers | Active | `RefreshDatabase`, mail fakes, notifications, route assertions. |
| Faker, Mockery, Collision | Active dev packages | Test/dev support from Laravel skeleton. |
| Laravel Pint | Available | Code style tool, not currently documented as required for every task. |
| Laravel Pail | Available | Local log tailing through Composer dev scripts. |

## Packages Not Currently Used For Planned Features

No current dependency proves implementation of Gmail SMTP, Google OAuth/Socialite, OTP packages, 2FA packages, chatbot, FastAPI, OpenAI API, Vue, React, or payment gateway integration.
