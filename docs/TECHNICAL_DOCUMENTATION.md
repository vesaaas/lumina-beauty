# Lumina Beauty Laravel Technical Documentation

Generated from source inspection on 2026-07-25.

## Application Overview

Lumina Beauty is a Laravel 13 e-commerce application for cosmetic and beauty products. It provides a public storefront, product catalog browsing, product filtering, product detail pages, favorites/wishlist, guest and authenticated carts, checkout, order persistence, order status email notifications, contact/about page email messages, customer registration/login/password reset, and a protected admin dashboard for catalog, order, customer, discount, and reporting views.

The application is built as a traditional server-rendered Laravel MVC app. Blade renders nearly all UI. JavaScript in `public/assets/js/storefront.js` progressively enhances the Blade pages with carousels, product galleries, search suggestions, custom filter dropdowns, account modal behavior, mobile navigation, and scroll reveal effects. There are no Vue components in this repository. `resources/js/app.js` is empty and Vite/Tailwind are present but the active storefront/admin styling is static CSS in `public/assets/css`.

The project appears suitable as a portfolio/thesis-scale commerce platform or internal prototype. It is not yet production-complete for real payments, tax/shipping rules, inventory operations, auditing, observability, or enterprise authorization.

## Architecture

### High-Level Architecture

The codebase follows Laravel MVC:

- Models: `app/Models` define catalog, commerce, and user entities using Eloquent relationships, casts, route binding, and soft deletes.
- Views: `resources/views` contains storefront layouts/pages/components, admin layouts/pages, auth pages, and email templates.
- Controllers: `StorefrontController`, `AdminController`, and `AccountAuthController` handle request orchestration, validation, persistence, and response selection.
- Routes: `routes/web.php` registers all browser routes, including public storefront routes, custom auth routes, and an `auth + admin` middleware-protected admin group.
- Middleware: `EnsureUserIsAdmin` blocks non-admin users from admin routes.
- Database: migrations create users, sessions, cache, jobs, catalog tables, commerce tables, metadata columns, phone support, and product deletion protection.
- Mail: `StorefrontPageMessage` and `OrderStatusNotification` render Blade emails.
- Assets: public CSS/JS are directly linked from layouts. Vite assets exist but are effectively unused by the visible app.

### Request Flow

1. The browser requests a URL registered in `routes/web.php`.
2. Laravel resolves route middleware. Public routes pass directly. Admin routes require `auth` and `admin`; guests are redirected to `/admin/login` through `bootstrap/app.php`.
3. Laravel route model binding resolves `{product}`, `{category}`, and `{brand}` by slug because the models override `getRouteKeyName()`.
4. Controller methods validate input with `$request->validate()` or `validateWithBag()`, query Eloquent models, eager-load relationships where needed, and prepare view data.
5. Storefront product data is converted through `Product::toStorefrontArray()`, which normalizes prices, images, stock flags, brand/category names, and metadata.
6. Blade templates render HTML. Layouts inject CSRF tokens, product JSON, navigation, account modal, and CSS/JS references.
7. Forms submit back to Laravel with CSRF protection. Cart/favorite/order mutations update session state or database rows.
8. Checkout wraps order creation and stock decrement inside a transaction and uses `lockForUpdate()` to reduce overselling risk.
9. Mailables are sent synchronously after checkout and selected admin order status changes.
10. The browser receives a Blade page or redirect with flash status/errors.

### Design Patterns Used

- MVC: primary application organization.
- Active Record: Eloquent models own persistence and relationships.
- Repository-like query helpers: private controller helpers such as `cartQuery()`, `favoritesQuery()`, `applyProductFilters()`.
- Route model binding: slugs are used for SEO-friendly catalog URLs.
- Blade components: `product-card`, `page-heading`, and `premium-filter-bar` encapsulate repeated UI.
- Transaction script: checkout logic is a procedural workflow inside `StorefrontController::placeOrder()`.
- Mailable objects: email construction is encapsulated in `app/Mail`.
- Middleware authorization: `EnsureUserIsAdmin` centralizes the admin gate.
- Soft delete protection: `Product` uses `SoftDeletes` and blocks force deletes through a model deleting hook.

## Folder Structure

- `app/Http/Controllers`: three application controllers plus base controller. Most business logic currently lives here.
- `app/Http/Middleware`: custom admin middleware.
- `app/Mail`: order status and storefront page mailables.
- `app/Models`: Eloquent entities for users, catalog, cart/favorites, orders, and order items.
- `app/Providers`: default service provider; currently no custom boot logic.
- `bootstrap`: Laravel 13 app bootstrap. `bootstrap/app.php` defines routing, middleware aliases, and guest redirects.
- `config`: Laravel defaults plus `lumina.php`, the seed catalog source.
- `database/migrations`: schema creation and evolution.
- `database/seeders`: admin user and catalog seeders.
- `database/factories`: `UserFactory` for tests.
- `public/assets/css`: active storefront/admin CSS.
- `public/assets/js`: active storefront JavaScript.
- `resources/css` and `resources/js`: Vite/Tailwind entry files, but not meaningfully used by layouts.
- `resources/views`: all Blade pages, components, admin templates, auth templates, and email templates.
- `routes`: web and console route definitions.
- `tests/Feature`: meaningful coverage for auth, checkout, order status mail, product deletion, and filtering.
- `tests/Unit`: default placeholder unit test.
- `.ddev`: DDEV local development configuration.
- `screenshots`: captured UI screenshots for reference.

## Routes

### Public Storefront

- `GET /`: home page.
- `GET /products`: product listing with search, metadata filters, price filters, and sorting.
- `GET /products/{product}`: product detail, resolving product by slug and requiring `is_active`.
- `GET /categories`: category index.
- `GET /categories/{category}`: category detail with filters, resolving category by slug.
- `GET /brands`: brand index.
- `GET /brands/{brand}`: brand detail, resolving brand by slug.
- `GET /hot-trends`: products marked `is_hot_trend`.
- `GET /sales`: products with non-null `sale_price`.
- `GET /about-us`, `POST /about-us`: about page and message submission.
- `GET /contact-us`, `POST /contact-us`: contact page and message submission.

### Favorites

- `GET /favorites`: shows favorite products owned by authenticated user or session.
- `POST /favorites/{product}`: toggles favorite.
- `DELETE /favorites/{product}`: removes favorite.

Favorites use either `user_id` or `session_id` as owner identity. Unique indexes prevent duplicate favorites per owner/product.

### Cart and Checkout

- `GET /cart`: cart page.
- `POST /cart/{product}`: add product to cart.
- `PATCH /cart/products/{product}` and `DELETE /cart/products/{product}`: guest cart quantity/remove endpoints.
- `PATCH /cart/items/{cartItem}` and `DELETE /cart/items/{cartItem}`: authenticated cart item quantity/remove endpoints.
- `GET /checkout`: checkout form.
- `POST /checkout`: creates order and order items.
- `GET /orders/{order}/thank-you`: order confirmation page.

Guest carts are stored in the session under `guest_cart`. Authenticated carts are rows in `cart_items`.

### Authentication

- `GET /account`, `GET /login`, `GET /register`: redirect to home with the account modal opened.
- `POST /login`: customer login.
- `POST /register`: customer registration.
- `POST /logout`: logout.
- `GET /forgot-password`, `POST /forgot-password`, `GET /reset-password/{token}`, `POST /reset-password`: customer password reset flow.
- `GET /admin/login`, `POST /admin/login`: admin login flow.

### Admin

Admin routes are grouped under `prefix('admin')`, `name('admin.')`, and middleware `['auth', 'admin']`.

- Dashboard: `/admin/dashboard`.
- Products: list, create, store, edit, update. There is intentionally no product delete route.
- Categories: list, create, update, delete if unused.
- Brands: list, create, update, delete if unused.
- Orders: list, show, update status.
- Users: registered user list.
- Reports: basic sales reporting.
- Discounts: products currently on sale.
- Settings: read-only environment/application summary.

## Controllers

### `StorefrontController`

This is the main storefront and commerce workflow controller.

- `home()`: renders `home` with shared storefront data.
- `products(Request)`: searches active products by product name, brand name, or category name; applies optional category, product type, property, gender, size, min/max price, and sort filters; renders `products.index`.
- `product(Product)`: aborts inactive products and renders `products.show`.
- `categories()` and `brands()`: render index pages using shared data.
- `category(Request, Category)`: renders products for one category, with metadata and price filters.
- `brand(Brand)`: renders active products for one brand.
- `favorites(Request)`: loads favorite products for current user/session.
- `toggleFavorite(Request, Product)`: creates or deletes a favorite row for the owner/product.
- `removeFavorite(Request, Product)`: deletes favorite row for owner/product.
- `cart(Request)`: renders cart with items and total.
- `addToCart(Request, Product)`: validates active/stock status and stores quantity in session for guests or `cart_items` for users.
- `updateCart(Request, CartItem)`: verifies ownership, clamps quantity to 1-99, checks stock, updates row.
- `updateGuestCart(Request, Product)`: same for guest session cart.
- `removeCart(Request, CartItem)` and `removeGuestCart(Request, Product)`: remove cart items.
- `checkout(Request)`: renders checkout page.
- `placeOrder(Request)`: validates customer/shipping fields, locks product rows, checks stock, creates order/order items, decrements stock, clears cart, and sends pending order email.
- `thankYou(Order)`: renders order confirmation.
- `about()`, `contact()`: render content pages.
- `sendAboutMessage()` and `sendContactMessage()`: validate named error bags and send `StorefrontPageMessage`.
- `hotTrends()` and `sales()`: render curated product listing pages.

Important helpers:

- `viewData()`: loads all active products, categories, brands, cart count, favorites count, and hero image URLs for every storefront page.
- `ownerAttributes()`: chooses `user_id` or `session_id`.
- `cartItems()`, `cartCount()`, `cartTotal()`: normalize guest and user cart data.
- `ensureProductCanBePurchased()`: centralizes active/stock validation.
- `applyProductFilters()` and `applyProductSorting()`: query mutation helpers.
- `makeOrderNumber()`: creates unique `LB-YYYYMMDD-ULID` order numbers.

Possible improvements:

- Move checkout, cart, favorites, filtering, and shared view data into dedicated services/actions.
- Replace repeated `viewData()` global loading with view composers, cached navigation data, and page-specific product queries.
- Queue mail after transaction commit instead of synchronous `Mail::send()`.
- Add authorization checks for viewing thank-you orders.
- Add throttling to contact/about and auth endpoints.

### `AdminController`

This controller handles admin dashboard, CRUD-like catalog management, order operations, users, reports, discounts, and settings.

- `dashboard()`: computes total sales, monthly sales/target progress, revenue per month, top categories, low stock products, top selling products, recent customers, pending orders, sale products, average order value, and simulated traffic/conversion metrics.
- `products()`: paginates products with brand/category/images.
- `createProduct()` and `editProduct(Product)`: render product form with category/brand lists and metadata options.
- `storeProduct()` and `updateProduct()`: validate attributes, create/update product, upload new images.
- `categories()`, `storeCategory()`, `updateCategory()`, `deleteCategory()`: category management. Delete is blocked when products exist.
- `brands()`, `storeBrand()`, `updateBrand()`, `deleteBrand()`: brand management. Delete is blocked when products exist.
- `orders()`, `order(Order)`, `updateOrder()`: order list, order detail, status update. Status changes to `processing` or `completed` send customer email.
- `users()`: customer list with order counts.
- `reports()`: latest 20 orders, total revenue, total discounts.
- `discounts()`: products with sale prices.
- `settings()`: read-only settings page.

Important helpers:

- `productAttributes()`: validates product metadata, price, sale price, stock, images, booleans, and generated slug.
- `productFilterOptions()`: returns allowed values from `Product` constants.
- `uniqueSlug()`: produces unique product slugs.
- `storeImages()`: writes uploaded images to the public disk and creates `product_images` rows.

Possible improvements:

- Add separate Form Request classes for product/category/brand/order validation.
- Add image deletion/reordering/replacement support.
- Replace dashboard raw MySQL `DATE_FORMAT()` with database-agnostic reporting or document MySQL/MariaDB as a hard requirement.
- Add admin confirmation UI for destructive category/brand deletes.
- Add admin audit logging for product/order changes.

### `AccountAuthController`

This controller implements custom customer/admin authentication rather than Laravel Breeze/Fortify scaffolding.

- `register()`: validates first name, last name, email, phone, password; creates a non-admin user; logs them in; merges guest commerce state; regenerates session.
- `login()`: attempts `Auth::attempt()`, merges guest cart/favorites, regenerates session.
- `showAdminLogin()` and `adminLogin()`: separate admin login page and admin-only credential check.
- `logout()`: logs out, invalidates session, regenerates token.
- `showForgotPassword()`, `sendPasswordResetLink()`, `showResetPassword()`, `resetPassword()`: customer-only password reset flow. Admin password reset is intentionally blocked through the customer flow.
- `attachGuestCommerce()`: merges session cart, database guest cart rows, and session-owned favorites into the authenticated user.

Possible improvements:

- Use `PasswordRule::defaults()` or stronger password rules on registration.
- Confirm password on registration.
- Normalize/validate phone consistently at checkout and registration.
- Add login throttling.
- Add email verification for customer accounts.

## Models

### `User`

Fields: `name`, `email`, `phone`, `password`, `is_admin`, timestamps, `email_verified_at`, `remember_token`.

Casts: `email_verified_at` datetime, `is_admin` boolean, `password` hashed.

Relationships:

- `cartItems()`: has many `CartItem`.
- `favorites()`: has many `Favorite`.
- `orders()`: has many `Order`.

Implementation notes:

- Uses PHP attributes `#[Fillable]` and `#[Hidden]` instead of traditional protected arrays.
- Uses `HasFactory` and `Notifiable`.
- Admin/customer distinction is a boolean, not a role/permission model.

### `Category`

Fillable: `name`, `slug`, `description`, `image`.

Route key: `slug`.

Relationship: `products()` has many `Product`.

### `Brand`

Fillable: `name`, `slug`, `description`, `image`.

Route key: `slug`.

Relationship: `products()` has many `Product`.

### `Product`

Fillable:

- `category_id`, `brand_id`
- `name`, `slug`, `description`
- `product_type`, `properties`, `gender`, `size`
- `price`, `sale_price`, `stock`
- `is_featured`, `is_new_arrival`, `is_hot_trend`, `is_active`

Casts:

- `properties` array
- prices decimal
- stock integer
- booleans for merchandising/active flags

Constants:

- `PRODUCT_TYPES`: cleanser, serum, moisturizer, toner, face mask, hair, makeup, fragrance types.
- `PROPERTIES`: Hydrating, Brightening, Anti-Aging, Oil Control, Sensitive Skin, Long Lasting.
- `GENDERS`: Women, Men, Unisex.
- `SIZES`: 30ml, 50ml, 100ml, 200ml.

Relationships:

- `category()`: belongs to `Category`.
- `brand()`: belongs to `Brand`.
- `images()`: has many `ProductImage`, ordered by `sort_order`.
- `orderItems()`: has many `OrderItem`.

Accessors/helpers:

- `activePrice`: sale price if present, otherwise regular price.
- `isOutOfStock()`: stock <= 0.
- `isAvailableForPurchase()`: active and in stock.
- `imageUrls()`: local public disk URL or external URL.
- `primaryImage()`: first image or Unsplash fallback.
- `toStorefrontArray()`: serialized storefront representation.

Traits: `HasFactory`, `SoftDeletes`.

Deletion design:

- Force deletes throw `LogicException`.
- Soft deletes are allowed.
- This protects historical order item references.

### `ProductImage`

Fillable: `product_id`, `path`, `alt_text`, `sort_order`.

Relationship: belongs to `Product`.

### `CartItem`

Fillable: `user_id`, `session_id`, `product_id`, `quantity`.

Relationships: belongs to `User`, belongs to `Product`.

Ownership model:

- Authenticated cart rows use `user_id`.
- Guest database cart rows can use `session_id`, though the current storefront primarily stores guest cart data in `guest_cart` session array.

### `Favorite`

Fillable: `user_id`, `session_id`, `product_id`.

Relationships: belongs to `User`, belongs to `Product`.

Ownership model:

- Authenticated favorites use `user_id`.
- Guest favorites use `session_id` database rows.

### `Order`

Fillable:

- `user_id`, `order_number`
- customer identity and shipping fields
- `status`, `subtotal`, `discount_total`, `total`

Casts: `subtotal`, `discount_total`, `total` as decimal strings with 2 precision.

Relationships:

- `user()`: belongs to `User`.
- `items()`: has many `OrderItem`.

### `OrderItem`

Fillable:

- `order_id`, `product_id`
- denormalized `product_name`, `brand_name`, `category_name`
- `unit_price`, `quantity`, `line_total`

Casts: `unit_price`, `line_total` decimals.

Relationships:

- `order()`: belongs to `Order`.
- `product()`: belongs to `Product`.

Design rationale:

- Order items snapshot product, brand, category, and unit price so order history remains readable after catalog changes.

## Database

### Core Laravel Tables

`0001_01_01_000000_create_users_table.php` creates:

- `users`: id, name, unique email, nullable email verification timestamp, password, remember token, timestamps.
- `password_reset_tokens`: email primary key, token, created_at.
- `sessions`: database session store with id, nullable user_id, IP, user agent, payload, last activity.

`0001_01_01_000001_create_cache_table.php` creates `cache` and `cache_locks`.

`0001_01_01_000002_create_jobs_table.php` creates `jobs`, `job_batches`, and `failed_jobs`.

### User Additions

`2026_05_12_000001_add_is_admin_to_users_table.php` adds boolean `is_admin` default false.

`2026_06_24_000002_add_phone_to_users_table.php` adds nullable `phone`.

### Catalog Tables

`2026_06_23_000001_create_catalog_tables.php` creates:

- `categories`: name, unique slug, nullable description/image, timestamps.
- `brands`: name, unique slug, nullable description/image, timestamps.
- `products`: category_id and brand_id foreign keys with cascade delete, name, unique slug, description, price, nullable sale_price, stock, merchandising booleans, active boolean, timestamps.
- `product_images`: product_id with cascade delete, path, nullable alt text, sort order, timestamps.

Important issue:

- The initial products foreign keys use `cascadeOnDelete()` for category and brand. Admin UI blocks deleting categories/brands with products, but database-level cascade could delete products if deletion occurs outside that controller. Because products are order-sensitive, `restrictOnDelete()` would be safer.

### Commerce Tables

`2026_06_23_000002_create_commerce_tables.php` creates:

- `favorites`: nullable user_id, nullable indexed session_id, product_id, timestamps, unique user/product and session/product pairs.
- `cart_items`: nullable user_id, nullable indexed session_id, product_id, quantity, timestamps, unique user/product and session/product pairs.
- `orders`: nullable user_id with null-on-delete, unique order_number, customer fields, shipping fields, string status, monetary totals, timestamps.
- `order_items`: order_id cascade delete, nullable product_id originally null-on-delete, denormalized product fields, unit price, quantity, line total.

### Product Metadata

`2026_06_24_000001_add_filter_metadata_to_products_table.php` adds:

- `product_type`
- `properties` JSON
- `gender`
- `size`

It also backfills metadata for known seeded product slugs.

Important issue:

- Columns are nullable in the database but required by admin validation. If code creates products outside admin validation, storefront filtering may encounter incomplete metadata.

### Deletion Protection

`2026_07_03_000001_protect_products_from_physical_deletion.php` adds:

- `deleted_at` soft delete column to products.
- changes `order_items.product_id` foreign key to `restrictOnDelete()`.

Combined with `Product::booted()`, this blocks permanent product deletion and preserves order references.

## Seeders

### `AdminUserSeeder`

Creates or updates one admin user using:

- `ADMIN_EMAIL`, default `admin@luminabeauty.test`
- `ADMIN_NAME`, default `Lumina Admin`
- `ADMIN_PASSWORD`, default `Admin123!`

Security note: the default admin password is predictable and should never be used in production.

### `CatalogSeeder`

Creates five categories: Skin Care, Hair Care, Makeup, Perfume, Body.

Creates brands and products from `config/lumina.php`. The seeded catalog contains 10 products with external Unsplash images, prices, sale prices, metadata, stock, featured/new/hot trend flags, and descriptions.

### `DatabaseSeeder`

Runs admin and catalog seeders.

## Authentication and Authorization

Authentication uses Laravel's default session guard and Eloquent user provider configured in `config/auth.php`.

Customer features:

- Register from account modal.
- Login from account modal.
- Password reset through Laravel password broker.
- Logout from account modal/header/admin layout.

Admin features:

- Admin users are identified by `users.is_admin`.
- Admin creation is seeder-only.
- Admin login has a separate page and rejects non-admin users.
- Admin password reset is blocked from the customer reset flow.

Authorization:

- `EnsureUserIsAdmin` checks `request()->user()?->is_admin`.
- No Laravel policies or gates are present.
- No role/permission package is installed.

Best practices followed:

- Session regeneration after login/register.
- Session invalidation and CSRF token regeneration on logout.
- Passwords are hashed through the `User` model cast.
- Admin routes use route-group middleware.

Best practices missing:

- Login and contact throttling.
- Email verification.
- Password confirmation on sensitive admin actions.
- Policies/gates for explicit resource authorization.
- Multi-role permission model for non-trivial admin teams.
- Restricting order confirmation access to order owner or signed URL.

## Views

### Storefront Layout

`resources/views/layouts/app.blade.php` defines:

- Global head metadata and CSRF token.
- Google font and static stylesheet imports.
- Promo strip, sticky header, brand link, nav, category/brand dropdowns.
- Favorites/cart counters.
- Account modal with login/register/admin login tabs.
- Search strip.
- Main content slot.
- Product JSON payload for JS search.
- Footer.
- Lucide and storefront JS scripts.

It depends on `viewData()` variables: products, categories, brands, cart/favorites counts, and flash/errors.

### Storefront Pages

- `home.blade.php`: hero carousel, brand carousel, new arrivals carousel, sales carousel, category strip, about preview.
- `products/index.blade.php`: page heading, filter bar, product grid, empty state.
- `products/show.blade.php`: gallery, product details, stock badge, price, add-to-cart/favorite forms.
- `categories/index.blade.php`: category cards using first four category models.
- `categories/show.blade.php`: category heading, filters, product grid.
- `brands/index.blade.php`: brand grid with a representative product image.
- `brands/show.blade.php`: product grid for one brand.
- `cart/index.blade.php`: cart rows, quantity steppers, line totals, discount subtotal, unavailable item detection, checkout link.
- `favorites/index.blade.php`: saved product grid and remove forms.
- `checkout/index.blade.php`: shipping/customer form and order summary.
- `orders/thank-you.blade.php`: order confirmation and items.
- `about.blade.php`: marketing copy plus about message form.
- `contact.blade.php`: contact form.
- `placeholders/hot-trends.blade.php`: hot trend product grid.
- `placeholders/sales.blade.php`: sale product grid.
- `account/index.blade.php`: mostly unused because `/account` redirects to the modal.

### Components

- `components/product-card.blade.php`: reusable product tile with gallery images, sale/out-of-stock badges, favorite form, metadata links, price, see more, and add-to-cart.
- `components/premium-filter-bar.blade.php`: reusable GET filter form with custom select UI, hidden search preservation, price inputs, sort selector, apply/reset controls.
- `components/page-heading.blade.php`: reusable heading section.

### Admin Views

- `admin/layout.blade.php`: sidebar, topbar, flash/errors, Lucide setup, content slots.
- `admin/login.blade.php`: standalone admin login screen.
- `admin/dashboard.blade.php`: metric cards, revenue/category canvas charts, target progress, low stock, top products, recent customers, recent orders.
- `admin/products/index.blade.php`: paginated product table.
- `admin/products/form.blade.php`: create/edit form for product details, metadata, images, flags.
- `admin/categories/index.blade.php`: create and inline update/delete categories.
- `admin/brands/index.blade.php`: create and inline update/delete brands.
- `admin/orders/index.blade.php`: paginated order table.
- `admin/orders/show.blade.php`: customer info, status update form, item table.
- `admin/users/index.blade.php`: registered users with role and order count.
- `admin/reports.blade.php`: revenue/discount/order summaries and latest sales table.
- `admin/discounts.blade.php`: products with sale prices.
- `admin/settings.blade.php`: read-only app/database/upload/admin notes.

### Auth Views

- `auth/forgot-password.blade.php`: standalone password reset request form.
- `auth/reset-password.blade.php`: standalone reset form.
- `auth/login.blade.php` and `auth/register.blade.php`: present but routes redirect to the modal, so these templates are effectively unused and partly inconsistent with `AccountAuthController::register()` because `auth/register.blade.php` submits `name` and `password_confirmation`, while the controller expects `first_name`, `last_name`, `phone`, and no confirmation.

### Email Views

- `emails/orders/status.blade.php`: table-based HTML order email for pending, processing, and completed states.
- `emails/storefront-page-message.blade.php`: table-based HTML email for about/contact messages.

## Frontend

### JavaScript

`public/assets/js/storefront.js` provides:

- Product JSON parsing from `#storefront-products-json`.
- Currency formatting in EUR.
- Product card template function, currently not used by server-rendered pages.
- Counter display updates from server-rendered counts.
- Custom filter select behavior.
- Auto-submit filters and price inputs.
- Product detail gallery previous/next controls.
- Product card rotating image galleries.
- Carousel progress bars.
- Hero carousel rotation.
- Header search suggestions from embedded product JSON.
- Account modal open/close and tab switching.
- Smooth product-section scrolling.
- IntersectionObserver scroll reveal.
- Mobile menu/dropdown behavior.
- Lucide icon initialization.

Empty functions:

- `renderFavoritesPage()` and `renderCartPage()` are no-ops, likely leftover from an earlier client-rendered approach.

Potential bugs:

- `setupGallery()` assumes previous/next buttons exist when `[data-gallery]` exists.
- Client-generated `productCard()` interpolates product data directly into HTML strings. Server-generated product names are trusted from the database, but this pattern would need escaping if used with user-generated data.
- Search results use `product.images[0]` without fallback.

### CSS

`public/assets/css/styles.css` is the storefront stylesheet. It defines variables, typography, header/nav, promo strip, account modal, product cards/grids/carousels, forms, filter bar, cart/checkout layouts, responsive behavior, and animation/reveal states.

`public/assets/css/admin.css` is the admin stylesheet. It defines admin variables, login screen, fixed sidebar, topbar, panels, metric cards, dashboard grids, tables, forms, status pills, pagination, and responsive admin layouts.

`resources/css/app.css` imports Tailwind 4 and configures sources/theme, but layouts do not include the Vite-built CSS. This is technical debt unless the project intentionally keeps static public CSS.

### Vue

No Vue files or Vue package are present.

## Forms and Validation

Validation is performed inline in controllers.

Validated flows:

- Customer registration: first name, last name, unique email, phone regex, password min 8.
- Login/admin login: email and password.
- Password reset request/reset: email/token/password confirmation for reset.
- Contact/about messages: name, email, topic for contact, message length.
- Product create/update: category/brand existence, name, description, metadata enum values, properties array, price, sale price less than price, stock, image validation.
- Category/brand create/update: unique name and optional description.
- Order checkout: customer name/email/phone, shipping address/city/country.
- Order status update: one of pending, processing, completed, cancelled.

Error handling:

- Most form failures redirect back with Laravel validation errors.
- About/contact use named error bags.
- Cart/stock failures use `ValidationException::withMessages(['cart' => ...])`.
- Some `abort()` calls produce HTTP exceptions for empty checkout, forbidden cart access, inactive products, and invalid admin access.

Missing best practices:

- Form Request classes.
- Named error bags for account modal login/register/admin forms.
- Consistent old input display across all admin forms.
- Client-side progressive validation is minimal.
- Contact/about forms lack spam protection or throttling.

## Business Logic

### Product Management

Admins can create and update products, assign category/brand, set metadata filters, set price/sale price, stock, upload images, and mark products as featured/new/hot/active. Slugs are regenerated from the product name and made unique. Products cannot be permanently deleted and no admin product delete route is registered.

### Catalog Browsing

Products are loaded with brand/category/images and converted to arrays for Blade. Product listing supports search, filters, and sorting. Category pages reuse filters except category selection. Brand pages show active products for the brand without extra filters.

### Cart

Guests store cart quantities in session. Authenticated users store cart rows in `cart_items`. Quantities are clamped between 1 and 99. Active status and stock are checked before add/update. Login/registration merges guest cart into the authenticated cart.

### Wishlist/Favorites

Favorites are stored in the database for both users and guests. Guests are identified by session ID. The toggle endpoint creates or deletes the owner/product row. Login/registration merges session favorites into the user account.

### Checkout and Orders

Checkout accepts guest and authenticated users. It validates shipping/customer fields, rejects empty carts, locks products in a transaction, rechecks availability/stock, calculates subtotal, discount total, total, creates order and items, decrements stock, clears the cart, sends a pending email, and redirects to thank-you.

### Admin Dashboard

The dashboard aggregates sales, orders, customers, products, pending orders, sale products, monthly progress, revenue-by-month, top categories, low stock products, top selling products, and recent customers/orders. It renders lightweight canvas charts without a charting library.

### Customer Features

Customers can register, login, reset passwords, merge guest commerce state, maintain favorites, checkout with saved identity defaults, and receive order emails.

## Services

No dedicated service classes exist. Business workflows are embedded in controllers and model helpers. Strong candidates for service extraction:

- `CartService`
- `FavoriteService`
- `CheckoutService`
- `ProductFilterService`
- `ProductImageService`
- `AdminDashboardMetrics`
- `OrderNumberGenerator`

## Middleware

`EnsureUserIsAdmin` checks the authenticated user and aborts with HTTP 403 if `is_admin` is false or missing.

`bootstrap/app.php` aliases this middleware as `admin` and redirects guests accessing `admin/*` routes to `admin.login`; other guests go to the named `login` route, which opens the home account modal.

## Policies

No policies are defined. The code relies on route middleware and ad hoc ownership checks:

- Admin access: middleware.
- Cart item access: `StorefrontController::ownsCartItem()`.
- Product visibility: active checks in storefront methods.

Missing policies:

- `OrderPolicy` for thank-you/order visibility.
- `CartItemPolicy`.
- `ProductPolicy` for admin actions.
- `CategoryPolicy` and `BrandPolicy` if roles become more granular.

## Mail and Notifications

### Mail

`OrderStatusNotification`:

- Constructor receives `Order` and notification type.
- Loads missing items.
- Subject/headline/intro vary by `pending`, `processing`, `completed`.
- Uses `emails.orders.status`.
- Sent on checkout pending and admin status changes to processing/completed.

`StorefrontPageMessage`:

- Constructor receives page, subject, attributes.
- Sets reply-to from visitor email/name.
- Uses `emails.storefront-page-message`.
- Sent to `ADMIN_EMAIL` or mail from address.

### Notifications

Laravel's built-in password reset notification is used through the password broker. No custom notification classes exist.

Important improvement:

- Both custom mailables use `Queueable` but are sent synchronously. Use queued mail with a running queue worker and `afterCommit()` behavior for checkout.

## Configuration and Environment

Important env variables:

- App: `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`, locale values.
- Database: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- Admin seed: `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`.
- Sessions: `SESSION_DRIVER`, `SESSION_LIFETIME`, `SESSION_ENCRYPT`, `SESSION_DOMAIN`.
- Filesystem: `FILESYSTEM_DISK`.
- Queue: `QUEUE_CONNECTION`.
- Cache: `CACHE_STORE`.
- Mail: `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`.
- External services: AWS, Postmark, Resend, SES, Slack keys are configured but not directly used by app code.

Observed `.env`:

- Local app name `Lumina Beauty`.
- DDEV URL `https://lumina-beauty.ddev.site`.
- MariaDB connection to DDEV host `db`.
- Database-backed sessions/cache/queues.
- SMTP port 1025 for local mail capture.

Security note:

- `.env` exists locally and is ignored by git. It contains a real app key and default admin password for local development. Production must rotate secrets and replace default credentials.

## Packages

### Composer Direct Packages

- `laravel/framework`: Laravel application framework.
- `laravel/tinker`: interactive REPL for development.
- `fakerphp/faker`: test/factory fake data.
- `laravel/pail`: local log tailing.
- `laravel/pao`: agent-optimized PHP test output.
- `laravel/pint`: PHP formatter.
- `mockery/mockery`: mocking library for tests.
- `nunomaduro/collision`: improved CLI exception output.
- `phpunit/phpunit`: test framework.

### NPM Direct Packages

- `vite`: frontend build/dev server.
- `laravel-vite-plugin`: Laravel integration for Vite.
- `tailwindcss`: Tailwind 4 CSS framework.
- `@tailwindcss/vite`: Tailwind Vite plugin.
- `concurrently`: runs PHP server, queue listener, logs, and Vite in the `composer dev` script.

Observed `npm ls` also reports several extraneous packages under `node_modules`, suggesting dependency tree cleanup may be useful.

## Testing

Test command run:

```bash
php artisan test
```

Result:

- 13 tests passed.
- 42 assertions passed.
- Duration: 437 ms.

### Feature Tests

`AccountAuthTest`:

- Registration stores phone number and creates non-admin customer.
- Customer password reset sends reset notification.
- Admin password reset is blocked from customer flow.

`CheckoutTest`:

- Authenticated checkout persists order/items, clears cart, decrements stock, sends pending email.
- Out-of-stock products cannot be added to cart.
- Checkout rejects cart items that become out of stock.
- Admin product delete route is not registered.
- Product soft delete preserves order item reference.
- Product force delete is blocked.
- Processing/completed order status changes email customer.

`ProductFilterTest`:

- Seeded products can be filtered by combined category/gender/size metadata.

`ExampleTest`:

- Home page returns 200.

### Unit Tests

Only default placeholder true-is-true test exists.

### Missing Tests

- Guest checkout.
- Guest cart merge edge cases.
- Favorite merge edge cases.
- Order thank-you authorization.
- Admin product create/update validation.
- Image upload.
- Category/brand delete blocking.
- Search behavior.
- Contact/about mail.
- Login throttling once added.
- Browser/UI tests for JavaScript-driven controls.

## Performance

Current risks:

- `StorefrontController::viewData()` loads all active products with relationships on every storefront page. This will become expensive as catalog size grows.
- Product listing calls `get()` instead of pagination.
- Brand index eager-loads `brandModels` with all `products.images` for every brand.
- Navigation categories/brands and shared product JSON are rebuilt each request.
- Product JSON embedded into every storefront page grows with catalog size.
- Dashboard performs many independent aggregate queries.
- Price filtering and sorting use `COALESCE(sale_price, price)`, which may not use normal indexes efficiently.
- JSON `whereJsonContains` on `properties` may need generated columns or specialized indexes depending on database engine.
- Synchronous mail sending adds latency to checkout/admin status updates.

Recommended optimizations:

- Paginate product listing and brand/category grids.
- Cache nav category/brand lists and homepage merchandising collections.
- Remove full product JSON from pages that do not need instant search or move search to AJAX endpoint.
- Add indexes for `products.is_active`, `category_id`, `brand_id`, `sale_price`, `is_hot_trend`, `is_new_arrival`, and order `status/created_at`.
- Queue mail.
- Use a reporting table/cache for dashboard metrics at scale.
- Add image thumbnails and responsive image handling.

## Security

Strengths:

- CSRF protection is present on forms.
- Validation exists for all main mutations.
- Password hashing uses Laravel cast.
- Admin routes are middleware-protected.
- Session regeneration is done after login/register.
- Product order references are protected from force deletion.
- Stock is checked inside a transaction with product row locks during checkout.

Vulnerabilities and gaps:

- No throttling on login, admin login, password reset, contact, or about forms.
- Default seeded admin password is documented in `.env.example`.
- Admin authorization is a boolean, not role/permission-based.
- Thank-you route exposes orders by numeric ID and has no owner/signed-token check.
- Contact/about forms have no CAPTCHA, honeypot, rate limit, or abuse protection.
- External scripts from `unpkg.com` and Google fonts are loaded without SRI.
- External Unsplash images are heavily used; product imagery depends on third-party availability and leaks requests.
- Product/category/brand cascade delete constraints conflict with the goal of protecting historical product/order integrity if database deletions occur outside controller paths.
- No CSP, security headers, or cookie secure enforcement in app-specific config.
- Admin forms lack password confirmation for sensitive actions.
- No audit logging.
- No payment integration, so real financial security/compliance is not addressed.

## Laravel Best Practices Followed

- Eloquent relationships and route model binding.
- Validation before persistence.
- CSRF tokens in forms.
- Middleware-protected admin group.
- Database migrations and seeders.
- Mailables for email rendering.
- Feature tests for critical checkout/auth behavior.
- Soft deletes for products.
- Model casts for booleans, arrays, prices, hashed password.
- Transaction and row locking during checkout.
- Named routes across views/controllers.

## Laravel Best Practices Missing

- Form Request classes.
- Policies/gates for resource authorization.
- Service/action classes for complex workflows.
- Queued mail/jobs.
- Pagination on public catalog pages.
- Database indexes tuned to query patterns.
- Rate limiting for auth/contact.
- API/resource layer or DTOs for complex product serialization.
- View composers or cached shared view data.
- Comprehensive test coverage.
- Deployment hardening: config/cache/routes/views optimization, HTTPS cookie settings, CSP/security headers.

## Refactoring Opportunities

Immediate refactor candidates:

- Extract `CheckoutService` from `StorefrontController::placeOrder()`.
- Extract `CartService` for guest/user cart normalization and merging.
- Extract `FavoriteService`.
- Extract `ProductFilterService` used by product and category pages.
- Extract `StorefrontViewDataComposer` or cached composer for shared layout data.
- Move admin product validation to `StoreProductRequest` and `UpdateProductRequest`.
- Remove or implement empty JS functions.
- Remove unused `auth/login.blade.php`, `auth/register.blade.php`, or align them with controller fields.
- Decide whether Vite/Tailwind is active. Either use `@vite` assets or remove unused build setup.

## Technical Debt

- Controller classes are carrying business logic and query composition.
- Static CSS is large and not integrated with Vite despite Vite/Tailwind config.
- Public product lists are unpaginated.
- Auth UI has duplicate/inconsistent templates.
- Admin dashboard metrics include simulated values (`trafficSessions`, conversion based on order/product count).
- No real payment, shipping, tax, discount code, invoice, or refund workflows.
- Guest cart is partly session-only and partly database-supported through merge code, creating two representations.
- Product image management only adds images; it does not delete, reorder, or mark primary.
- No SKU field, inventory reservation, or product variants.
- Category homepage hardcodes category slugs/classes and uses only four categories, while seed includes five.
- Brand carousel includes brands that may not exist in the database and links them to the brands index.

## Potential Bugs

- `orders/{order}/thank-you` allows anyone who knows an order ID to view order details.
- `auth/register.blade.php` is incompatible with `AccountAuthController::register()`.
- Category homepage link `body-care` does not match seeded category slug `body`.
- Dashboard `DATE_FORMAT()` is MySQL/MariaDB-specific; tests use SQLite but do not cover dashboard.
- Product metadata columns are nullable, but controller assumes valid metadata for new products.
- `viewData()` can produce stale or heavy data on every page and embeds all products in JSON.
- Category/brand database foreign keys cascade product deletion if deleted outside admin controller.
- `storefront.js` product card string builder would need escaping if ever used with unsafe data.
- No max length validation for checkout phone.
- Account modal opens on any validation error, including unrelated page forms, because it checks `$errors->any()`.

## Project Status Report

Current state:

- Functional Laravel 13 storefront/admin prototype.
- Core catalog, cart, favorites, checkout, orders, status emails, contact emails, and admin screens exist.
- Database schema is coherent for a small catalog.
- Feature tests cover several high-value commerce and auth paths.
- UI is polished and mostly server-rendered.
- Application is not yet production-ready for real commerce.

What should be built next:

1. Fix access control for order thank-you pages.
2. Add rate limiting to login/admin login/password reset/contact/about.
3. Replace default admin password before any shared environment.
4. Paginate product listing and reduce global product loading.
5. Extract checkout/cart/favorites into services and add tests around guest flows.
6. Add payment integration or explicitly mark checkout as manual/offline ordering.
7. Add admin image deletion/reordering and product archive UI.
8. Add policies, audit logs, and stronger admin authorization.

## Future Roadmap

### Immediate Improvements

- Protect order confirmation URLs.
- Add route throttling.
- Fix `body-care` category link.
- Remove or repair unused auth views.
- Queue order/contact emails.
- Add missing indexes.

### Short-Term Improvements

- Extract services/actions from controllers.
- Add Form Requests.
- Add public pagination.
- Add full guest cart/favorites test coverage.
- Add image management.
- Add order search/filtering in admin.

### Medium-Term Improvements

- Add payments, shipping, tax, invoices, refunds.
- Add product reviews/ratings.
- Add SKUs/product variants.
- Add inventory reservation and low-stock alerts.
- Add customer order history.
- Add admin audit trail.

### Long-Term Improvements

- Introduce roles/permissions.
- Build a proper reporting layer.
- Add asynchronous search endpoint.
- Add CDN-backed image storage and responsive image processing.
- Add observability, exception tracking, metrics, and backups.

### Enterprise-Level Improvements

- Multi-tenant or multi-store support.
- ERP/inventory integration.
- Event-driven order lifecycle.
- Warehouse fulfillment workflows.
- Fraud/risk controls.
- CI/CD with static analysis, browser tests, security scanning, and deployment gates.
- Formal data retention and privacy controls.

## Code Quality Review

Strengths:

- The app is understandable and cohesive.
- Eloquent relationships are straightforward.
- Checkout uses a transaction and row locks, which is the right direction for inventory correctness.
- Product force deletion is explicitly blocked.
- UI components reduce some Blade duplication.
- Tests cover critical regressions around checkout, stock, admin reset blocking, and product deletion.

Weaknesses:

- Controllers are too large and own too much business logic.
- Authorization is shallow outside the admin middleware.
- Public product loading strategy will not scale.
- No dedicated domain services or Form Requests.
- No real payment/shipping/tax model.
- Frontend build setup and actual asset usage are inconsistent.
- Several admin/reporting behaviors are prototype-level.

Professional recommendation:

Treat Lumina Beauty as a strong Laravel MVC prototype. Before production use, focus on security hardening, order privacy, rate limiting, service extraction, database indexing, pagination, queued mail, and tests for guest commerce. After that, add real commerce infrastructure: payments, shipping, tax, customer order history, robust inventory, and admin auditability.
