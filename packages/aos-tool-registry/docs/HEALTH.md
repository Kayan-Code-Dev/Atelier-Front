# Health & Availability

## ToolHealthRegistry

Stores per-tool health records (`healthy`, message, checkedAt). Defaults to healthy when no record exists.

## ToolAvailabilityManager

Runtime overlays: `available` | `degraded` | `unavailable`.

Discovery excludes `unavailable` tools. Degraded tools remain discoverable but should be surfaced carefully to Planner/Prompt.
