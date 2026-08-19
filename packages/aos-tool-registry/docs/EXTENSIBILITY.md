# Extensibility

## Adding a new Domain Plugin

1. Create a binding package (contracts-first).
2. On boot, obtain `ToolRegistrar`.
3. Register Provider → Capabilities → Tools → Intents.
4. Never inject Domain models into Planner/Gateway.

## Categories

Extend `ToolCategory` enum when introducing a new business vertical (prefer ADR for new categories).

## Future bridges

Optional adapter may project `ToolDescriptor` → `aos-tools` `ToolManifest` + handler registration — outside this sprint’s scope.
