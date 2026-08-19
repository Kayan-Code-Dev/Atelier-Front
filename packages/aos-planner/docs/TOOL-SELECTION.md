# Tool Selection

`ToolSelector` discovers and **orders** tools; it never invokes them.

## Input

- Ordered `toolPlan` from Intent Analyzer
- `PlatformPlanningContext.availableTools` as registry snapshot (empty = all plan tools allowed)

## Output

`ToolSelection`: selected tool ids, `PlanStep` list, missing tools.

Permission and subscription are **not** applied here — separate validators own those gates.
