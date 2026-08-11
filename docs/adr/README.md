# Architecture Decision Records

Architecture Decision Records document important technical decisions and their tradeoffs. They are not changelogs. When a future change replaces a documented decision, create a new ADR and link the old one rather than silently rewriting history.

## ADRs

- [001: Laravel monolith with Blade](001-laravel-monolith-with-blade.md)
- [002: Single administrator model](002-single-administrator-model.md)
- [003: Product soft deletion instead of physical deletion](003-product-soft-deletion.md)
- [004: Order item snapshots preserve historical commerce data](004-order-item-snapshots.md)
- [005: Security-first modernization before AI/chatbot work](005-security-first-before-ai.md)

## Template

```markdown
# ADR-NNN: Title

Status:
Date:

## Context

## Decision

## Alternatives Considered

## Consequences

## Related Files
```
