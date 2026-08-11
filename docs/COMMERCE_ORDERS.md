# Commerce And Orders

Read with [DATABASE.md](DATABASE.md), [STOREFRONT.md](STOREFRONT.md), and [AUTH_SECURITY.md](AUTH_SECURITY.md).

## Guest Cart

Guests store cart quantities in the session under `guest_cart`. Guest update/remove routes use product IDs:

- `PATCH /cart/products/{product}`
- `DELETE /cart/products/{product}`

Quantities are clamped between 1 and 99 and checked against active product/stock rules.

## Authenticated Cart

Authenticated users store cart rows in `cart_items` keyed by `user_id` and `product_id`. Update/remove routes use cart item IDs:

- `PATCH /cart/items/{cartItem}`
- `DELETE /cart/items/{cartItem}`

`StorefrontController::ownsCartItem()` verifies ownership before update/delete.

## Cart Merge

`AccountAuthController::attachGuestCommerce()` merges:

- session `guest_cart`
- database cart rows with current `session_id`
- favorite rows with current `session_id`

into the authenticated user's `cart_items` and `favorites`.

## Checkout

Routes:

- `GET /checkout`
- `POST /checkout`

`StorefrontController::placeOrder()` validates customer and shipping fields and rejects an empty cart. Authenticated verified customers create an order immediately. Guests first receive a six-digit checkout email OTP; the order is not created until the OTP succeeds.

## Transaction Boundary

Order creation is wrapped in `DB::transaction()`. Inside the transaction:

- products are reloaded with `lockForUpdate()`
- active/stock checks are repeated
- order row is created
- order item rows are created
- stock is decremented
- cart is cleared

The pending order email is sent after the transaction commits. Guest checkout OTP happens before this transaction; pending guest checkout attributes are stored in the server-side session until verification succeeds.

## Stock Checks

`ensureProductCanBePurchased()` rejects inactive products, out-of-stock products, and quantities above available stock. Checkout repeats this check after row locking.

Never allow negative inventory.

## Order Items And Snapshots

Order items store snapshots:

- product name
- brand name
- category name
- unit price
- quantity
- line total

These snapshots preserve history even if product catalog data changes later.

## Status Lifecycle

Current statuses:

- `pending`
- `processing`
- `completed`
- `cancelled`

Allowed transitions:

- `pending -> processing`
- `pending -> cancelled`
- `processing -> completed`
- `processing -> cancelled`

Terminal statuses:

- `completed`
- `cancelled`

Invalid transitions do not update the order, send email, or create an `order.status_updated` audit log.

Current emails:

- Checkout sends `pending`.
- Admin change to `processing` sends processing email.
- Admin change to `completed` sends completed email.
- Changes to `cancelled` do not send a dedicated email.

## Thank-You Page

Route: `GET /orders/{order}/thank-you`

Renders `resources/views/orders/thank-you.blade.php` with loaded order items.

Access rules:

- authenticated orders can be viewed only by the owning user
- guest orders can be viewed only by the checkout session that created the order

## Authorization And Privacy

Implemented:

- Authenticated cart item ownership checks.
- Order confirmation ownership/session checks.
- Admin order management behind `auth` and `admin` middleware.

Known gaps:

- Customer order history route is not present.
- Resource-level authorization/policies remain planned.
