# AOS AI Response Engine (`dressnmore/aos-response`)

**Sprint 20 — v0.20.0** — Completes the first AI Core Platform cycle.

Module: `aos.response`

```text
User → Conversation → Planner → Execution Plan → Gateway → Tools → Response Engine → Final AI Response
```

## Components

| Component | Role |
|-----------|------|
| `ResponseEngine` | Orchestrates final reply + events |
| `ResponseBuilder` | Professional unified message |
| `ResultFormatter` | Per-tool localized sentences |
| `ResultAggregator` | Merge multi-tool outcomes |
| `ErrorResponseGenerator` | Friendly errors (no stack traces) |
| `LocalizationService` | `ar` / `en` |
| `PlanStepExecutor` | Runs plan steps via Tool Gateway (or simulator) |
| `EndToEndAiOrchestrator` | Full cycle entry point |
| `ConversationReplyGenerator` | Conversation/UI payload shape |

## Usage

```php
$orchestrator = EndToEndAiOrchestrator::createDefault($eventBus);
$result = $orchestrator->handle('احجز فستان', 'tenant_1', 'ar');
echo $result->response()->message();
```

## Out of scope

WhatsApp / social / email campaigns · LLM orchestrator · smart memory · multi-agent

## Docs

See [`docs/`](./docs/).

## Smoke

```bash
php scripts/aos-response-smoke.php
```
