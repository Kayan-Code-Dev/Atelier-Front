# AOS Conversation Engine (`dressnmore/aos-conversation`)

**Sprint 2** — Conversation lifecycle module for the DressnMore Agent Operating System.

## Purpose

Provide a **channel-agnostic, AI-agnostic Conversation Engine** that owns only the conversation lifecycle:

- identity & tenant scope isolation token
- state machine
- single ownership model
- sessions
- messages (opaque content)
- timeline audit trail
- domain events

This package does **not** know about OpenAI, WhatsApp, Business Tools, Knowledge, Planner, or Tenant Ops.

## Lifecycle

```
New → Active ⇄ WaitingCustomer / WaitingHuman / HumanHandling / Paused
                 ↓
              Resolved → Closed → Archived
```

Supported operations:

| Operation | Effect |
|-----------|--------|
| Start | Create aggregate (`New`), optionally activate |
| Resume | From `Paused`/`Resolved`/`New` back to interactive |
| Pause | → `Paused` |
| Close | End open session → `Closed` |
| Archive | `Closed` → `Archived` |
| Transfer ownership | AI / Human / SharedAssist / System (policy-guarded) |
| Assign human | Ownership → Human + status toward human handling |
| Return to AI | Ownership → AI + timeline `ReturnedToAi` |
| Add message | Append message + timeline |
| Add timeline event | Explicit timeline entry |
| Summary placeholder | Store opaque summary stub |

Illegal transitions throw `IllegalStateTransition`.

## Aggregate

**Root:** `Conversation`

**Children / value objects:**

- `ConversationSession` (many per conversation)
- `ConversationMessage`
- `Timeline` / `TimelineEvent`
- IDs: `ConversationId`, `SessionId`, `MessageId`, `TenantScopeId`
- Enums: `ConversationStatus`, `ConversationOwnership`

**Guards:**

- `ConversationStateMachine`
- `OwnershipPolicy`
- Specifications: `ConversationIsOpenForMessaging`, `ConversationIsHumanOwned`

## Responsibilities

| Layer | Responsibility |
|-------|----------------|
| Domain | Invariants, FSM, ownership, timeline, events |
| Application | `ConversationManager`, `ConversationLifecycle` |
| Infrastructure | `InMemoryConversationRepository` only (Sprint 2) |
| Module | `aos.conversation` registration with AOS Kernel |

## Ownership model

Exactly one owner at a time:

- `AI`
- `Human`
- `SharedAssist`
- `System`

Terminal conversations cannot change ownership. Ongoing work cannot transfer **to** `System`.

## Extension points

1. **`ConversationRepositoryInterface`** — swap InMemory for durable persistence later.
2. **Domain events** — subscribe via `EventBusInterface` (Planner/AI/Channels in later sprints).
3. **Timeline types** — extend `TimelineEventType` carefully; keep payloads scalar.
4. **Specifications** — add query rules without bloating the aggregate.
5. **`ConversationManager`** — application façade for use-case orchestration.

## Non-goals (this sprint)

- Eloquent / migrations / DB
- HTTP Controllers / APIs
- OpenAI / providers
- WhatsApp / channels
- Business tools, knowledge, planner, memory, context packs

## Module registration

Service provider: `AosConversationServiceProvider`

Module name: `aos.conversation`

Enable in `config/aos.php`:

```php
'enabled_modules' => [
    // ...
    'aos.conversation' => true,
],
'feature_flags' => [
    'conversations' => true,
],
```

## Tests

- Package unit tests under `packages/aos-conversation/tests`
- App integration: `tests/Unit/Aos/AosConversationEngineTest.php`
- Smoke: `php scripts/aos-conversation-smoke.php`
