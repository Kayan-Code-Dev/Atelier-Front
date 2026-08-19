# ADR-006: Curated Context Bundle Before Model Calls

- **Status:** Accepted
- **Date:** 2026-08-06
- **Context:** Dumping entire tenant databases into prompts is unsafe, expensive, and low quality.
- **Decision:** Mandate an **AI Context Engine** that builds a single permission-aware `AgentContextBundle` with selective hydration, compression, and citations before any model call.
- **Consequences:** Orchestrator never “sees” raw unconstrained tenant dumps. Tool reads remain the source of fresh operational facts.
