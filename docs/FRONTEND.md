# Frontend

Read with [ARCHITECTURE.md](ARCHITECTURE.md), [STOREFRONT.md](STOREFRONT.md), and [ADMIN_PANEL.md](ADMIN_PANEL.md).

## Architecture

The current frontend is server-rendered Blade plus static CSS and vanilla JavaScript.

Active assets:

- `public/assets/css/styles.css`
- `public/assets/css/admin.css`
- `public/assets/js/storefront.js`

Layouts link these assets directly. The app does not currently use Vue or React.

## Blade Layouts And Views

- Storefront layout: `resources/views/layouts/app.blade.php`
- Admin layout: `resources/views/admin/layout.blade.php`
- Storefront pages: `home`, `products`, `categories`, `brands`, `cart`, `favorites`, `checkout`, `about`, `contact`, order thank-you, sales/hot-trends placeholders.
- Admin pages: dashboard, products, categories, brands, orders, users, reports, discounts, settings.
- Auth pages: admin login, forgot/reset password, and in-progress OTP verification. The temporary admin confirm-password page has been removed.

## Components

Reusable Blade components:

- `resources/views/components/product-card.blade.php`
- `resources/views/components/page-heading.blade.php`
- `resources/views/components/premium-filter-bar.blade.php`

## JavaScript

`public/assets/js/storefront.js` progressively enhances:

- product cards
- Lucide icon initialization
- account modal behavior
- gallery interactions
- filter/dropdown behavior
- mobile/navigation/search UI
- scroll/reveal interactions

## Styling

The storefront and admin use custom CSS. Naming is class-based and purpose-specific. Keep UI changes consistent with the existing Blade/CSS structure unless a redesign is explicitly requested.

## Account Modal

The account modal is embedded in `resources/views/layouts/app.blade.php` and opened through route flash state or frontend behavior. `/account`, `/login`, and `/register` redirect to home with the modal flag.

## Admin UI

The admin UI uses `resources/views/admin/layout.blade.php` and `public/assets/css/admin.css`. It uses sidebar navigation, admin metrics, forms, tables, status pills, and icon buttons.

Sensitive admin actions use one reusable password confirmation modal defined in the admin layout. Category deletion, brand deletion, and order status updates open the modal instead of rendering permanent password inputs beside each action.

On narrow hover-capable viewports, the admin sidebar collapses to an icon rail and expands on hover/focus without shifting main content. On touch-only narrow viewports, a compact sidebar toggle controls the same rail expansion.

## Registration Password UX

The active registration form is in `resources/views/layouts/app.blade.php`. It displays one helper sentence matching the backend password rule: minimum 8 characters, uppercase, lowercase, number, and symbol.

## Spam Fields

Contact/about forms include hidden honeypot and timing fields styled by `public/assets/css/styles.css`.

## External Assets

Current external frontend dependencies:

- Lucide icons from `https://unpkg.com`
- Google Fonts from `https://fonts.googleapis.com` and `https://fonts.gstatic.com`
- Unsplash images from `https://images.unsplash.com`

## CSP Implications

`SecurityHeaders` sends CSP as Report-Only and currently allows the external sources above plus inline scripts/styles. If CSP becomes enforced, frontend changes must account for these dependencies or replace them.

## Responsive Behavior

Responsive behavior is implemented in custom CSS and progressive JavaScript. Verify storefront/admin pages at mobile and desktop widths when changing layout, navigation, filters, cards, forms, or modals.

## Vite And Tailwind Status

`vite.config.js`, `resources/css/app.css`, and `resources/js/app.js` exist. Tailwind 4 and Vite are installed. Current visible layouts do not use `@vite`; they directly reference public static assets.

Do not claim Tailwind/Vite is the active production styling path unless the layouts change.
