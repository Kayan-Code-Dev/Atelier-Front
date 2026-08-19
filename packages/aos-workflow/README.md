# AOS Workflow & Automation Engine (`dressnmore/aos-workflow`)

**Sprint 12** — Enterprise workflow orchestration and automation core.

## Purpose

Introduce a contracts-first automation engine that executes reusable workflows with triggers, conditions, tasks, retries, human-in-the-loop checkpoints, and runtime monitoring — without database coupling or business-task implementation details.

## Workflow Lifecycle

Draft → Testing → Published → Running → Paused → Completed → Cancelled → Archived

Each workflow is versioned through `WorkflowVersion`.

## Execution Pipeline

```
Trigger
 → Load Workflow
 → Validate
 → Build Context
 → Evaluate Conditions
 → Execute Tasks
 → Handle Errors
 → Retry
 → Complete
```

## Task Types

AI Task · Business Tool Task · Human Task · Approval Task · Notification Task · Delay Task · Decision Task · Condition Task · Parallel Task · Sequential Task

## Trigger Types

Incoming Message · Comment · Lead Created · Customer Created · Reservation Created · Invoice Created · Payment Received · Time Trigger · Cron Trigger · Manual Trigger · API Trigger (Future)

## Retry Strategy

- Immediate
- Exponential Backoff
- Manual Retry
- Dead Letter

`RetryManager` provides policy behavior for future adapters.

## Extension Points

1. Replace `TaskDispatcher` with real adapters for AI/Human/Business execution.
2. Implement additional trigger adapters while keeping Domain intact.
3. Replace in-memory repository with persistent storage adapter.
4. Add richer condition/rule evaluators without modifying engine contracts.
5. Subscribe to workflow domain events for observability and auditing.

## Architecture Decisions

- DDD + Hexagonal + SOLID + PSR
- Contracts-first boundaries (`WorkflowEngineInterface`, repository contracts)
- No DB / No Laravel Models / No controllers / No API transport
- Engine is product-agnostic and independent from DressnMore business flows
- Supports execution monitoring, workflow metrics, and human-in-the-loop task types
