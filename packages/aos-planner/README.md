# AOS AI Planner (`dressnmore/aos-planner`)

**Sprint 18 (v0.18.0)** — Platform Planner Engine on top of Sprint 6 heuristic planner.

Module: `aos.planner`

## What this package does

Turns a user message + tenant planning context into an **Execution Plan** (identifiers + ordered steps only).

| Does | Does not |
|------|----------|
| Analyze intent (catalog / keywords) | Call LLM |
| Match capabilities | Execute tools |
| Select & order tools from registry view | Call Tool Gateway |
| Evaluate policy / permission / subscription | Mutate business data |
| Build `PlatformExecutionPlan` | Retry / workflow engine |

## Two planners

| Layer | Class | Context |
|-------|--------|---------|
| Sprint 6 | `Application\PlannerEngine` | Heuristic pipeline → `Domain\Plan\ExecutionPlan` |
| Sprint 18 | `Application\Platform\PlatformPlannerEngine` | Registry-oriented → `Domain\Platform\PlatformExecutionPlan` |

Sprint 6 remains for backward compatibility. Sprint 18 is the input contract for **Tool Gateway (Sprint 19)**.

## Platform pipeline

```
User Message → Planning Context → Intent Analyzer → Capability Matcher
 → Tool Selector → Policy / Permission / Subscription → Execution Plan Builder
 → Execution Plan (no execution)
```

## Docs

See [`docs/`](./docs/): ARCHITECTURE, PLANNER, INTENT-ANALYZER, CAPABILITY-MATCHER, TOOL-SELECTION, EXECUTION-PLAN, EVENTS, VALIDATION, SECURITY, EXTENSIBILITY, SPRINT18-DOD.

## Module

- Provider: `AosPlannerServiceProvider`
- Module: `aos.planner` @ `0.18.0`
- Contracts: `PlannerInterface`, analyzers, validators, `ExecutionPlanRepositoryInterface`
- Smoke (Sprint 6): `php scripts/aos-planner-smoke.php`

## Tests

```bash
cd packages/aos-planner
vendor/bin/phpunit
# or from root with path filter
```
