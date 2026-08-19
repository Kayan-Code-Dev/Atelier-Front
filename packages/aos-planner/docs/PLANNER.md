# Planner

`PlatformPlannerEngine` implements `PlannerInterface`.

## Responsibilities

1. Accept `PlatformPlanningContext`
2. Analyze intent (no LLM)
3. Match capabilities against registry view
4. Select ordered tools (no execution)
5. Enforce policy, permission, subscription
6. Build & persist `PlatformExecutionPlan`
7. Emit planning events

## Factory

`PlatformPlannerEngine::createDefault($eventBus, $registeredCapabilities?)` wires in-memory defaults for tests/demo.

## DI

Bound in `AosPlannerServiceProvider` as `PlannerInterface` → `PlatformPlannerEngine`.
