# ADR-003: Product Soft Deletion Instead Of Physical Deletion

Status: Accepted  
Date: 2026-08-09

## Context

Products may be referenced by historical orders. Physical deletion risks breaking order history and commerce reporting. The repository has a migration adding product soft deletes and changing order item product references to restrict deletion. The `Product` model blocks force delete.

## Decision

Products must not be physically deleted through normal application behavior. Use soft deletion/protective behavior and remove or avoid destructive product delete routes.

## Alternatives Considered

- Hard delete products and null order item references.
- Archive products through `is_active` only.
- Keep products physically deletable only when no orders exist.

## Consequences

- Order history remains intact.
- Admin product deletion is intentionally absent.
- Future catalog cleanup must use active/inactive/soft-delete semantics and preserve snapshots.

## Related Files

- `app/Models/Product.php`
- `database/migrations/2026_07_03_000001_protect_products_from_physical_deletion.php`
- `routes/web.php`
- `tests/Feature/CheckoutTest.php`
- [../DATABASE.md](../DATABASE.md)
