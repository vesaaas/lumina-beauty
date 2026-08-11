# Roadmap

Roadmap items are future work unless explicitly marked completed. Planned features are not implementation evidence. Read with [CURRENT_STATE.md](CURRENT_STATE.md), [AUTH_SECURITY.md](AUTH_SECURITY.md), and [ARCHITECTURE.md](ARCHITECTURE.md).

## Completed Foundations

- Laravel monolith with Blade storefront/admin.
- Catalog, favorites, cart, checkout, orders, order snapshots.
- Product soft deletion and order history preservation.
- Basic custom customer/admin auth and password reset.
- Initial audit logging.

## Phase 1: Security-First Modernization

Status: COMPLETED

Scope:

- complete remaining session/security/authorization/admin UX work
- standardize sensitive admin action confirmation
- define safe order status transitions
- address order thank-you privacy
- add regression tests for changed security/business rules

Completion criteria:

- documented sensitive action policy: completed
- order transition tests passing: completed
- order privacy behavior documented and tested: completed
- [CURRENT_STATE.md](CURRENT_STATE.md), [AUTH_SECURITY.md](AUTH_SECURITY.md), and [KNOWN_ISSUES.md](KNOWN_ISSUES.md) updated: completed

## Phase 2: Real Email Infrastructure

Status: NEXT / PLANNED

Scope:

- developer manually configures Gmail SMTP
- verify local/development and production-safe mail behavior
- do not commit SMTP credentials

Completion criteria:

- environment-only SMTP configuration documented
- password reset/order/contact/OTP mail verified through configured driver
- [EMAIL_INTEGRATIONS.md](EMAIL_INTEGRATIONS.md) updated without secrets

## Phase 3: Email Verification

Status: PLANNED / PARTIALLY STARTED IN WORKING TREE

Scope:

- six-digit email OTP
- hashed OTP storage
- expiry
- attempt limits
- resend cooldown
- rate limiting
- verified timestamp behavior

Completion criteria:

- complete verification UX
- resend/cooldown behavior implemented
- tests for success, invalid code, expired code, attempt limit, resend behavior
- no plaintext OTP storage/logging

## Phase 4: Google Authentication

Status: PLANNED

Scope:

- Laravel Socialite
- Google OAuth login
- account linking rules
- OAuth secret handling

Completion criteria:

- Socialite dependency added and documented
- OAuth routes/controllers implemented
- no OAuth secrets committed
- auth/security docs and tests updated

## Phase 5: Login 2FA

Status: PLANNED

Scope:

- secure second-factor design
- rate limiting and recovery behavior
- admin/customer policy decision

Completion criteria:

- ADR or security design documented
- server-side enforcement implemented
- regression tests added

## Phase 6: Guest Checkout Email Verification

Status: PLANNED

Scope:

- verify guest checkout email before or during order flow
- avoid blocking authenticated verified users unnecessarily
- preserve cart and checkout state

Completion criteria:

- guest verification flow implemented
- order privacy rules reviewed
- tests for guest/authenticated cases

## Phase 7: Authorization/Policies Final Review

Status: PLANNED

Scope:

- resource-level authorization
- order privacy
- admin action boundaries

Completion criteria:

- policies or equivalent authorization rules implemented where useful
- tests added for forbidden access
- security docs updated

## Phase 8: Product Knowledge Layer

Status: PLANNED

Scope:

- structured product metadata suitable for recommendations, search, and future AI features
- skin/product concerns if needed
- data quality and admin editing workflow

Completion criteria:

- schema/data design documented
- metadata managed through admin or seed flow
- tests for filtering/search behavior

## Phase 9: AI Chatbot

Status: PLANNED

Planned stack:

- React
- Python
- FastAPI
- OpenAI Responses API

Potential later AI:

- product recommendation logic
- customer skin-type conversations
- image analysis
- custom ML/data work
- local models where justified

Completion criteria:

- architecture decision documented before implementation
- chatbot isolated from core commerce risk
- secrets managed outside repository
- privacy/security reviewed

These later phases are not currently implemented.
