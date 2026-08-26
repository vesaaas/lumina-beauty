# Roadmap

Roadmap items are future work unless explicitly marked complete. Planned features are not implementation evidence. Read with [CURRENT_STATE.md](CURRENT_STATE.md), [AUTH_SECURITY.md](AUTH_SECURITY.md), and [ARCHITECTURE.md](ARCHITECTURE.md).

## Completed Foundations

- Laravel monolith with Blade storefront/admin.
- Catalog, favorites, cart, checkout, orders, and order snapshots.
- Product soft deletion and order history preservation.
- Customer/admin auth base flows.
- Admin dashboard and catalog/order management.

## Phase 1: Security & Authentication

Status: COMPLETE

Completed scope:

- Strong customer registration and password reset rules.
- Registration email OTP with hashed six-digit codes, expiry, resend, cooldown, attempts, rate limiting, and tests.
- Customer password-login email 2FA with hashed codes, session-bound challenge state, expiry, resend, cooldown, attempts, and tests.
- Separate admin password-login email 2FA with admin context isolation and tests.
- Guest checkout email OTP before order creation with session binding and tests.
- Gmail SMTP runtime delivery through environment-only configuration.
- Database queue support for auth/security email and `afterCommit()` mail dispatch after challenge persistence.
- Google OAuth through Laravel Socialite for customer accounts.
- Google OAuth policy: Google is the primary authentication factor and does not require an additional Lumina email OTP/2FA challenge.
- Admin accounts cannot authenticate through the customer Google OAuth flow.
- Secure session regeneration/logout and authenticated-page no-cache protection.
- Isolated named Laravel rate limiters for customer/admin login, 2FA verify/resend, registration, registration OTP, forgot/reset password, guest checkout OTP, contact, and about.
- Contact/About isolated throttling and honeypot/timing anti-spam.
- `OrderPolicy` and IDOR regression coverage for order confirmation access.
- Current-password re-authentication for sensitive admin mutations.
- Expanded audit logging for authentication, OTP/2FA, OAuth, password reset, guest checkout OTP, admin catalog changes, and order status changes.
- Configurable CSP/security headers and production HTTPS-only HSTS behavior.
- Checkout transaction, product row locking, stock validation, negative stock prevention, product soft deletion, and order-history protection.
- Security regression tests.

Verification:

- Phase 1 checkpoint full `ddev artisan test`: 139 tests, 827 assertions, 0 failures.

## Phase 2: Product Knowledge Layer

Status: COMPLETE

Completed scope:

- Structured beauty-product knowledge schema added to `products` with nullable JSON arrays, scalar usage fields, nullable tri-state factual attributes, compatibility placeholders, and supporting indexes.
- Product model casts, scopes, normalization helpers, active price handling, and `toKnowledgeArray()` added for safe structured catalog context.
- `App\Support\Catalog\ProductKnowledge` centralizes allowed vocabulary for skin/hair types, concerns, benefits, target areas, key ingredients, routine steps, usage frequencies, and tri-state attributes.
- `ProductKnowledgeService` supports active-by-default structured filtering for category, brand, product type, suitability, concern, benefit, ingredient, routine step, AM/PM and factual attributes, price, availability, and keyword search.
- `ProductRecommendationService` provides deterministic rule-based matching and ranking from explicit facts only. It is not AI or machine learning.
- `ProductComparisonService` returns customer-safe structured comparison data for active products.
- `SkincareRoutineService` builds deterministic skincare routines from active, in-stock Skin Care products ordered by cleanser, toner, serum, treatment, moisturizer, sunscreen.
- Admin product create/edit forms manage Product Knowledge fields through validated controls, preserving current-password re-authentication and audit logging.
- Catalog seeding includes conservative demo metadata only; unknown facts remain unknown.
- Tests cover knowledge filtering, null/empty semantics, deterministic recommendations, comparison, routines, admin validation/persistence, more-than-50-candidate regression behavior, AM/PM nullable filtering, customer-safe comparison, and storefront regression.

Completion criteria:

- COMPLETE: Schema/data design documented and implemented.
- COMPLETE: Product knowledge fields are maintainable through admin UI and shared vocabulary.
- COMPLETE: Tests cover the Product Knowledge Layer.
- COMPLETE: Documentation distinguishes implemented knowledge features from future chatbot behavior.

Verification:

- Full `ddev artisan test`: 160 tests, 878 assertions, 0 failures.

## Phase 3: AI Chatbot

Status: NEXT / NOT IMPLEMENTED

Planned next-phase architecture:

User -> React chatbot UI -> Python/FastAPI AI service -> OpenAI Responses API -> Lumina Beauty product retrieval -> structured Product Knowledge context -> assistant response.

Planned scope:

- React chatbot frontend.
- Python/FastAPI AI service.
- OpenAI Responses API integration.
- Calls into Lumina Beauty structured product knowledge/retrieval.
- Assistant responses grounded in the Phase 2 Product Knowledge context.
- Later advanced image analysis and other AI features.

Completion criteria:

- Architecture decision documented before implementation.
- Chatbot isolated from core commerce risk.
- Secrets managed outside repository.
- Privacy/security reviewed.
- Product Knowledge Layer completed first.

## Not Currently Implemented

- React chatbot frontend.
- Python/FastAPI AI service.
- OpenAI Responses API integration.
- Embeddings, vector search, or vector database.
- Payment gateway.
- Production deployment.
