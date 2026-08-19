# Architecture

**Status:** Frozen v1.0.0 (`ArchitectureVersion`)

High-level tree: Core, Conversations, Channels (Dashboard→Email), Agents (Sales→Custom), Knowledge, Campaigns, Automations, Integrations, AI Models, Memory, Reports, Settings.

Dependency rule: Presentation → Infrastructure → Application → Registry → Contracts → Domain.

AI Core compatibility is declared in `ArchitectureVersion::COMPAT` (planner, tools/gateway, tool-registry, response, tenant-ai, core).
