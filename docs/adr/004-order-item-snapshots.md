# ADR-004: Order Item Snapshots Preserve Historical Commerce Data

Status: Accepted  
Date: 2026-08-09

## Context

Product names, brands, categories, and prices can change after a customer places an order. Orders need historical line item data for receipts, admin review, and commerce integrity.

## Decision

Store snapshot fields on `order_items`: product name, brand name, category name, unit price, quantity, and line total. Keep an optional product reference for traceability, but do not rely on live product data to render order history.

## Alternatives Considered

- Render orders entirely from current product records.
- Store only product ID and quantity.
- Duplicate a larger product JSON payload per order item.

## Consequences

- Order history survives catalog changes.
- Product deletion must remain protected.
- Future order views and emails should prefer snapshot fields for historical display.

## Related Files

- `database/migrations/2026_06_23_000002_create_commerce_tables.php`
- `app/Http/Controllers/StorefrontController.php`
- `app/Models/OrderItem.php`
- `resources/views/emails/orders/status.blade.php`
- `resources/views/orders/thank-you.blade.php`
- [../COMMERCE_ORDERS.md](../COMMERCE_ORDERS.md)
