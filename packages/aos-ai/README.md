# AOS AI Provider Platform (`dressnmore/aos-ai`)

**Sprint 10** — Full LLM abstraction layer between AOS and any AI provider.

## Purpose

Allow AOS to **select, call, fallback, and meter** AI providers through contracts only — without coupling the rest of the platform to OpenAI, Claude, Gemini, or any SDK/HTTP client.

| Does | Does not |
|------|----------|
| Provider / model registry & selection | Ship real OpenAI / Anthropic SDKs |
| Capability-based filtering | Make network / HTTP calls |
| Budget / latency / tenant policies | Touch Database / Eloquent |
| Stub plugin execution + streaming | Expose Controllers / APIs |
| Fallback, retry, health, metrics | Bind Prompt/Planner internals |

## Provider Architecture

```
AiEngine
 → Selection Pipeline
 → AiProviderInterface (plugin)
 → Normalized AiResponse
```

Conceptual providers (stub plugins in Sprint 10):

OpenAI · Azure OpenAI · Anthropic Claude · Google Gemini · Ollama · llama.cpp · vLLM · OpenRouter · Future

## Selection Pipeline

```
Planning Result
 → Resolve Required Capabilities
 → Provider Filtering
 → Model Filtering
 → Policy Validation
 → Budget Validation
 → Health Check
 → Latency Check
 → Provider Selection
 → Execution
 → Response Normalization
```

Selection scores **cost + latency + priority + health**.

## Fallback Strategy

1. Rank eligible provider/model pairs  
2. Execute primary selection with retry  
3. On failure → mark unhealthy → activate next ranked provider  
4. Emit `FallbackActivated` / `ProviderFailed` events  

## Provider Registry

- `ProviderRegistry` + `ModelRegistry`  
- `ProviderFactory` creates stub plugins (swap for real adapters later)  
- Multiple providers active concurrently  

## Extension Points

1. Implement `AiProviderInterface` for real HTTP adapters in a future package  
2. Replace `ProviderSelector` scoring weights  
3. Tune `ProviderPolicyEngine` (budget/latency/compliance/tenant)  
4. Subscribe to AI domain events  
5. Disable / enable catalog entries without touching other modules  

## Architecture Decisions

- **Contracts first** — `AiEngineInterface` / `AiProviderInterface`  
- **No SDK / No HTTP** in Sprint 10 core  
- **Plugins** — providers are swappable without Domain changes  
- **Hexagonal** — in-memory stubs only  
- **Multi-provider** — cost/latency/capability aware selection  

## Module

- Provider: `AosAiServiceProvider`
- Module: `aos.ai`
- Feature flag: `ai_providers`
- Smoke: `php scripts/aos-ai-smoke.php`
