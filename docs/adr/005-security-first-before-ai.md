# ADR-005: Security-First Modernization Before AI/Chatbot Work

Status: Accepted  
Date: 2026-08-09

## Context

The roadmap includes Gmail SMTP, email verification, Google OAuth, login 2FA, guest checkout verification, product knowledge work, and eventually AI chatbot functionality. The current application still has security/admin UX work to finish first, including order status transitions, sensitive-action consistency, and order privacy.

## Decision

Complete security-first modernization before implementing AI/chatbot or other advanced roadmap features.

## Alternatives Considered

- Build chatbot/product AI features first.
- Configure external integrations before hardening auth/order/admin behavior.
- Defer security cleanup until production deployment.

## Consequences

- Roadmap work must not be mistaken for implemented functionality.
- Security and admin UX issues should be resolved before Gmail/OAuth/2FA/chatbot work.
- Future AI work should have a separate architecture decision before implementation.

## Related Files

- [../CURRENT_STATE.md](../CURRENT_STATE.md)
- [../AUTH_SECURITY.md](../AUTH_SECURITY.md)
- [../ROADMAP.md](../ROADMAP.md)
- [../KNOWN_ISSUES.md](../KNOWN_ISSUES.md)
