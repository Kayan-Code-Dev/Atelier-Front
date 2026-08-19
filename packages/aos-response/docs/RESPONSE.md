# Response

`ResponseEngine::generate(ResponseContext, AggregatedToolResults): FinalAiResponse`

Status: `success` · `partial_success` · `failed` · `empty`

`EndToEndAiOrchestrator::handle()` runs Planner → PlanStepExecutor → Aggregator → Engine.
