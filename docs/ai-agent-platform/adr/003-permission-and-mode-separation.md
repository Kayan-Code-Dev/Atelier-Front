# ADR-003: Separate Operating Mode from Permissions

- **Status:** Accepted
- **Date:** 2026-08-06
- **Context:** Owners need Assistant / Hybrid / Full Auto behavior without accidentally granting destructive capabilities.
- **Decision:** **Permission Engine** is the capability firewall (allow/deny/approve + ceilings). **Operating Mode** is an overlay that further restricts autonomy (especially outbound send and tool execution). Mode never expands permissions.
- **Consequences:** Product can change default mode safely. Security reviews focus on capability policy; UX reviews focus on mode.
