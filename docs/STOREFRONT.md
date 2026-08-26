# Storefront

Read with [COMMERCE_ORDERS.md](COMMERCE_ORDERS.md), [FRONTEND.md](FRONTEND.md), and [AUTH_SECURITY.md](AUTH_SECURITY.md).

## Routes And Controller

Storefront behavior is primarily in `routes/web.php` and `app/Http/Controllers/StorefrontController.php`.

## Home Page

Route: `GET /` -> `StorefrontController::home()` -> `resources/views/home.blade.php`

Shows hero slides, categories, new arrivals, sale products, and shared navigation/account/cart/favorite counts from `viewData()`.

## Products

Routes:

- `GET /products` -> `products.index`
- `GET /products/{product}` -> `products.show`

Products are loaded with brand, category, and images. Product route model binding uses `slug`. Inactive product detail pages abort with 404.

## Categories And Brands

Routes:

- `GET /categories`
- `GET /categories/{category}`
- `GET /brands`
- `GET /brands/{brand}`

Category and brand detail pages show active products. Category detail supports product metadata and price filters.

## Search And Filters

Product listing search matches product name, brand name, and category name. Filters include category, product type, property, gender, size, price min/max, and sorting.

Filter options come from product constants and category records.

## Product Details

Product detail pages show gallery images, product metadata, price/sale price, stock availability, cart button, and favorite button.

## Favorites

Routes:

- `GET /favorites`
- `POST /favorites/{product}`
- `DELETE /favorites/{product}`

Guests use `session_id` ownership. Authenticated users use `user_id`. Unique database constraints prevent duplicate owner/product rows.

## Cart

Routes:

- `GET /cart`
- `POST /cart/{product}`
- `PATCH /cart/products/{product}` and `DELETE /cart/products/{product}` for guests
- `PATCH /cart/items/{cartItem}` and `DELETE /cart/items/{cartItem}` for authenticated cart rows

Guest cart state is stored in session key `guest_cart`. Authenticated carts are stored in `cart_items`.

## Checkout Entry

Route: `GET /checkout` renders `resources/views/checkout/index.blade.php` with cart items and total. Authenticated verified customers can place orders directly. Guests must complete a session-scoped checkout email OTP before the order is created. Order creation is documented in [COMMERCE_ORDERS.md](COMMERCE_ORDERS.md).

## Account Modal

`/account`, `/login`, and `/register` redirect to home with the account modal opened. Modal UI lives in `resources/views/layouts/app.blade.php` and includes the `Continue with Google` link to `auth.google.redirect`. Auth pages also exist for dedicated reset/admin/OTP flows.

## Guest Versus Authenticated State

- Guests: session cart plus session-owned favorite rows.
- Authenticated users: database cart/favorite rows keyed by user ID.
- On registration, successful customer login 2FA, or successful Google OAuth: `GuestCommerceService` merges session cart, session-owned cart rows, and session-owned favorites into the user account.

## Content Pages

Routes:

- `GET/POST /about-us`
- `GET/POST /contact-us`

POST routes validate input, throttle submissions, and send `StorefrontPageMessage`.
They use isolated `contact` and `about` named rate limiters plus honeypot/timing fields.

## Known UI/Business Limitations

- Checkout has no payment gateway.
- Guest checkout email verification is implemented with a session-scoped OTP before order creation.
- Product Knowledge Layer services are implemented as Laravel/domain foundations for structured catalog retrieval, deterministic recommendations, comparison, and skincare routines. No chatbot UI or AI service is implemented yet.
- Shared `viewData()` loads broad catalog/navigation data for many pages; future optimization may use view composers or page-specific data.
