# Architecture

Sprint 18 adds a **Platform** planning layer beside the Sprint 6 heuristic pipeline.

| Layer | Contents |
|-------|----------|
| Contracts | `PlannerInterface`, analyzers, selectors, evaluators, plan builder, context provider, plan repository |
| Domain/Platform | Intent catalog, planning context, capability/tool selection, `PlatformExecutionPlan`, status |
| Application/Platform | Analyzer, matcher, selector, policy/permission/subscription, builder, `PlatformPlannerEngine` |
| Infrastructure | `InMemoryExecutionPlanRepository` |
| Domain/Events | PlanningStarted → … → PlanningCompleted / Rejected / Failed |

Sprint 6 (`PlannerEngine` + pipeline stages) is unchanged and still registered.

**Boundary:** planning produces identifiers and ordered steps only. Tool Gateway (Sprint 19) executes.
