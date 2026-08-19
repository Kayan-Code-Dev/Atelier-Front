# ADR-005: Hexagonal Channel Adapters + Tool Gateway

- **Status:** Accepted
- **Date:** 2026-08-06
- **Context:** Business logic in webhooks creates unmaintainable per-channel forks and security holes.
- **Decision:** Use Ports & Adapters for channels. All business mutations/reads from the agent go through **Business Tools Gateway** guarded by Permission Tickets. Webhooks only normalize and enqueue.
- **Consequences:** Domain teams expose tools once; every channel benefits. Audit and dry-run become consistent.
