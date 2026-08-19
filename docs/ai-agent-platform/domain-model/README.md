# AI Agent Domain Model — Index

**Status:** Accepted conceptual model (Single Source of Truth for domain language)  
**Depends on:** [Enterprise Architecture](../ENTERPRISE-ARCHITECTURE.md)  
**Scope:** Conceptual Domain Model only — **not** database design, ERD, class diagrams, APIs, or implementation.

## Purpose

1. Define every core concept in the AI Agent Platform.  
2. Unify language across product and engineering.  
3. Remove ambiguity before any technical design.  
4. Clarify responsibility and ownership of each concept.  
5. Describe logical relationships between concepts.

## Pack contents

| Document | Contents |
|----------|----------|
| [DOMAIN-MODEL.md](./DOMAIN-MODEL.md) | Master overview + conceptual diagram |
| [entities.md](./entities.md) | Domain entities (definition, lifecycle, ownership, aggregates) |
| [bounded-contexts.md](./bounded-contexts.md) | Bounded Contexts and responsibilities |
| [aggregates.md](./aggregates.md) | Aggregate boundaries |
| [value-objects.md](./value-objects.md) | Value Objects |
| [enumerations.md](./enumerations.md) | Domain enumerations |
| [relationships.md](./relationships.md) | Conceptual relationships |
| [ubiquitous-language.md](./ubiquitous-language.md) | Official glossary (one definition per term) |
| [domain-events.md](./domain-events.md) | Domain Events and when they fire |
| [invariants.md](./invariants.md) | Rules that must never break |
| [extensibility.md](./extensibility.md) | Designed extension points |

## Reading order

1. Ubiquitous Language  
2. Bounded Contexts  
3. Entities + Aggregates  
4. Value Objects + Enumerations  
5. Relationships + Events + Invariants  
6. Extensibility  

## Explicit non-goals

- No tables, columns, indexes, or migrations  
- No ORM/class mappings  
- No REST/GraphQL contracts  
- No code samples or framework choices  
