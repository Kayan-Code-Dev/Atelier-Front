# Sprint 18 — Definition of Done

- Package `packages/aos-planner` **v0.18.0** (`aos.planner`)
- Platform Planner Engine produces `PlatformExecutionPlan` without executing tools
- Intent Analyzer, Capability Matcher, Tool Selector, Policy / Permission / Subscription validators, Plan Builder, Planning Context, repository
- Contracts: Planner, IntentAnalyzer, CapabilityMatcher, ToolSelector, PolicyEvaluator, PlanBuilder, PlanningContextProvider, ExecutionPlanRepository
- Events: PlanningStarted, IntentResolved, CapabilityMatched, ToolSelected, PlanningCompleted, PlanningRejected, PlanningFailed
- Validation scenarios covered by PHPUnit
- Documentation pack under `docs/`
- Sprint 6 planner preserved
