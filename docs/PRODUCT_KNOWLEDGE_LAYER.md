# Product Knowledge Layer

Phase 2 adds a structured catalog knowledge foundation inside the Laravel monolith and MariaDB-compatible schema. It does not add an AI chatbot, React chatbot UI, FastAPI service, OpenAI API calls, OpenAI SDK, embeddings, vector database, or external AI integration.

## Purpose

The layer prepares product data for deterministic search, filtering, comparison, recommendation, and routine-building. A later chatbot phase can call Laravel/domain services to retrieve a small set of safe product context, but Phase 2 only stores and retrieves explicit catalog facts.

## Schema

Product identity continues to use the existing `products`, `brands`, and `categories` tables:

- `name`, `slug`, `description`
- `brand_id`, `category_id`
- `product_type`, `properties`, `gender`, `size`
- `price`, `sale_price`, `stock`, active/featured flags, images

The additive migration `2026_08_26_000001_add_product_knowledge_fields_to_products_table.php` adds:

- JSON arrays: `skin_types`, `hair_types`, `concerns`, `benefits`, `target_areas`, `key_ingredients`, `works_well_with`, `avoid_combining_with`
- Scalar usage fields: `routine_step`, `usage_instructions`, `usage_frequency`
- Nullable booleans: `am_suitable`, `pm_suitable`, `fragrance_free`, `cruelty_free`, `vegan`, `alcohol_free`, `non_comedogenic`
- Text warning field: `knowledge_warnings`
- Indexes: `(is_active, stock)` for active/purchasable retrieval and `routine_step` for routine step lookup

JSON arrays were chosen for small/medium catalog multi-value tags where MariaDB `JSON_CONTAINS` filtering is sufficient. Nullable booleans were chosen for factual attributes because `null` means unknown, not false. Scalar columns were chosen for routine step and usage frequency because they are common exact-match filters.

## Known vs Unknown

Missing factual metadata is unknown. It is not interpreted as false and does not match positive filters.

For JSON-array knowledge fields such as `skin_types`, `concerns`, `benefits`, and `key_ingredients`:

- `null` means unknown or not documented
- `[]` means explicitly known empty, not applicable, or intentionally cleared by an admin
- `['dry']`, `['hydrating']`, and similar arrays mean explicitly known positive values

Admin checkbox groups use hidden submission markers so clearing every checkbox is saved as an empty array instead of falling back to the product's previous values. Direct internal writes with invalid vocabulary values throw an exception instead of silently dropping invalid metadata.

For nullable product attributes:

- `true` means explicitly known yes
- `false` means explicitly known no
- `null` means unknown or not documented

The same retrieval principle applies to suitability, concerns, benefits, and ingredients: a product only matches a filter when that value is explicitly stored.

## Domain Options

`App\Support\Catalog\ProductKnowledge` is the authoritative vocabulary for:

- skin and hair types
- concerns and benefits
- target areas
- key ingredients
- routine steps and skincare routine order
- usage frequencies
- tri-state product attributes

Admin validation, Blade controls, services, and tests use this shared source instead of duplicating option lists.

## Product Model

`App\Models\Product` now casts the new JSON and nullable boolean fields, normalizes knowledge arrays on save, and exposes:

- `scopeActive()`
- `scopeAvailableForPurchase()`
- `scopeWithKnowledgeValue()`
- `hasKnowledgeValue()`
- `toKnowledgeArray()`

`toKnowledgeArray()` returns safe product context: identity, brand, category, structured suitability, ingredients, usage, attributes, compatibility placeholders, price, availability, and storefront URL. It excludes audit data, admin-only details, timestamps, authentication data, and internal security state.

## Retrieval

`App\Services\Catalog\ProductKnowledgeService` builds Eloquent queries for active products by default. It supports:

- category, brand, product type
- skin type, hair type
- concern, benefit, key ingredient
- routine step
- nullable product attributes, including AM/PM suitability
- min/max price
- in-stock availability
- keyword search across product name, description, brand, and category

Filters are validated against allow-lists where the schema has controlled values. Customer-facing retrieval excludes inactive products unless explicitly requested by internal code.

Example:

```php
app(\App\Services\Catalog\ProductKnowledgeService::class)->knowledge([
    'skin_type' => 'dry',
    'concern' => 'dehydration',
    'benefit' => 'hydrating',
    'max_price' => 50,
    'available' => true,
]);
```

## Recommendation Rules

`App\Services\Catalog\ProductRecommendationService` is deterministic rule-based matching, not AI or machine learning.

It only recommends active, in-stock products. Optional budget/category/product-type filters are applied in SQL before scoring. Scoring is explicit:

- skin type match: 4 points
- hair type match: 4 points
- benefit match: 3 points
- concern match: 3 points
- ingredient match: 2 points

Every explicitly requested categorical criterion must match before a product is eligible. Scoring is then used only for deterministic ranking among full matches.

Results are ordered by score descending, active price ascending, then product id ascending. Active price uses `sale_price` when present and otherwise `price`. Unknown metadata does not score or match.

The service narrows candidates in SQL using active/in-stock status, price/category/product-type filters, and explicit JSON criteria. It does not rely on an arbitrary latest-product cutoff before scoring.

## Comparison

`App\Services\Catalog\ProductComparisonService` compares active products as structured customer-safe data. Inactive products passed to the public comparison entry point are excluded. It returns:

- a `products` array with safe comparable fields
- a `differences` array listing fields whose values differ

Compared fields include brand, category, product type, current price, skin suitability, concerns, benefits, key ingredients, routine step, product attributes, and availability.

## Routine Building

`App\Services\Catalog\SkincareRoutineService` builds simple skincare routines from explicit metadata. It:

- is hard-limited to the Skin Care category
- requires active, in-stock products
- can filter by skin type, concern, and benefit
- only uses skincare routine steps: cleanser, toner, serum, treatment, moisturizer, sunscreen
- orders results by that skincare step order
- supports optional per-product max price filtering and an optional total budget by removing later routine steps first

It does not force skincare metadata onto haircare, makeup, fragrance, or body products. The service retrieves all matching active/in-stock skincare candidates for eligible routine steps and does not rely on an arbitrary latest-product cutoff.

## Admin Management

The admin product create/edit form now includes checkboxes, selects, tri-state selects, and textareas for knowledge fields. Admins never enter raw JSON.

Product create/update still runs through the existing protected flow:

- authenticated admin routes
- CSRF-protected form submission
- current-password re-authentication before mutation
- Laravel validation
- audit logging after create/update

Invalid knowledge values are rejected server-side. Unknown nullable attributes persist as `null`.

## Seeder Policy

`config/lumina.php` and `CatalogSeeder` include conservative demo catalog metadata to demonstrate filtering, recommendations, comparisons, and routine ordering. This metadata is demo catalog metadata, not verified dermatological or medical claims.

Ingredient metadata is only seeded where the current catalog itself supports it, such as the product name containing niacinamide and zinc. Unknown formulation facts, certifications, warnings, and compatibility rules remain unknown.

## Performance and Security

The retrieval layer uses Eloquent and SQL-side filters, eager loads brand/category/images, and supports limits so future callers can retrieve a small product set. No external search infrastructure was added.

MariaDB JSON filtering is used only for small/medium catalog controlled tags. The schema avoids speculative normalized tables and avoids one opaque knowledge blob.

Security boundaries:

- inactive products are excluded by default
- recommendation and routine results require in-stock products
- raw SQL is not built from untrusted input
- controlled filters are allow-listed
- knowledge output excludes admin/security internals
- no secrets, analytics, tracking, external product data fetches, or AI calls were introduced

## Future Chatbot Consumption

A later AI chatbot phase can call the Laravel services to retrieve a small structured product set, then pass that safe context to a separate AI layer. Phase 2 intentionally stops at deterministic Laravel/MariaDB catalog retrieval and structured data output.
