# Extensibility

1. Extend `PlatformIntentCatalog` rules (keywords, tool plans, capabilities, approvals)
2. Swap `IntentAnalyzerInterface` for a richer resolver later (still no execution here)
3. Inject `CapabilityMatcher` with live Tool Registry capability ids
4. Pass `availableTools` from `aos-tool-registry` discovery into planning context
5. Replace `InMemoryExecutionPlanRepository` with a durable tenant-scoped store
6. Tune `PolicyEvaluator` denylist / `SubscriptionValidator` plan maps
7. Keep Sprint 6 `PlannerEngine` for legacy consumers; prefer `PlannerInterface` for Gateway
