# ADR-004: Human Handover is First-Class

- **Status:** Accepted
- **Date:** 2026-08-06
- **Context:** Fully autonomous agents will fail on refunds, disputes, and edge cases. Ateliers need trust.
- **Decision:** Human Handover is a core module with explicit ownership (`AI` | `Human` | `SharedAssist`), escalation reasons, resume rules, and staff notifications. Hybrid Mode is the default go-live mode.
- **Consequences:** Phase 1 must ship handover before Full Auto. Analytics track containment and handover rate as primary quality signals.
