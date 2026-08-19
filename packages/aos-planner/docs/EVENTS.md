# Events

| Event | When |
|-------|------|
| `PlanningStarted` | Engine begins |
| `IntentResolved` | Analyzer finished |
| `CapabilityMatched` | Capabilities matched |
| `ToolSelected` | Tools ordered |
| `PlanningCompleted` | Terminal success or recorded rejection/failure |
| `PlanningRejected` | Intent/capability/policy/permission/subscription reject |
| `PlanningFailed` | Build failure or engine exception |

Sprint 6 also emits pipeline events (`PlanGenerated`, `ClarificationRequired`, …) from `PlannerEngine`.
