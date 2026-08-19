# AOS Permission & Policy Engine (`dressnmore/aos-permissions`)

**Sprint 5** — Official authorization guard for every AI action inside AOS.

## Purpose

Decide whether an action may proceed:

`Authorized` · `Denied` · `Approval Required` · `Human Escalation` · `Retry Later`

This package owns modes, capabilities, permissions, policies, risk, and approvals.

It does **not** contain DressnMore business logic, OpenAI, Planner, Prompt Engine, channels, tool implementations, Eloquent, Controllers, or APIs.

## Authorization Flow

```
Authorization Request
 → Load Context
 → Resolve Operating Mode
 → Resolve Capabilities
 → Resolve Permissions
 → Evaluate Policies
 → Evaluate Risk
 → Need Approval?
 → Decision
    ├─ Authorized
    ├─ Denied
    ├─ Approval Required
    └─ Human Required / Retry Later
```

Façade: `PermissionEngineFacade` / `AuthorizationManager`.

## Policy Engine

Policies are declarative (`PolicyDefinition`) with:

- type: Business / Security / Compliance / Operating / Channel / Tenant
- effect: one of the decision outcomes
- priority, optional minimum risk, capability/mode filters

`PolicyEngine` merges matched effects (Deny/Human beats Approval beats Authorized).

## Capability Model

Built-in capabilities (extensible via `CapabilityCode::custom()`):

Read Customer, Read Invoice, Create/Update/Cancel Reservation, Issue Invoice, Read Knowledge, Create/Assign Task, Send Notification, Generate Report, Execute Automation, Transfer Conversation, Approve Request.

Each capability declares default risk + required permission codes.

## Permission Model

Orthogonal dimensions:

- Role Based
- Capability Based
- Policy Based
- Approval Based
- Risk Based

Deny by default: missing capability or required permission → `Denied`.

## Approval Model

`ApprovalRequest` with status, decision, chain, timeout, expiration.

Outcomes `ApprovalRequired` / `HumanEscalation` open an approval request (in-memory repository in Sprint 5).

## Risk Evaluation

Levels: Low · Medium · High · Critical

High/Critical typically require approval; Full Auto + High/Critical may escalate to human.

## Operating Modes

Assistant · Hybrid · Full Auto · Read Only · Human Only · Maintenance (+ custom codes)

Mode overlays permissions — it does not replace them.

## Extension Points

1. Register capabilities / permissions / policies in registries
2. Custom operating modes via `OperatingModeCode::custom()`
3. Swap `ApprovalRepositoryInterface` for durable storage later
4. Extend authorization pipeline stages
5. Subscribe to domain events on `EventBusInterface`

## Architecture Decisions

- Contracts-first Hexagonal package depending only on `aos-core` + `aos-events`
- Identifier/code keyed registries (not PHP class names)
- No Tenant Ops / DressnMore domain coupling
- In-memory approval store for Sprint 5 only

## Module

- Provider: `AosPermissionsServiceProvider`
- Module: `aos.permissions`
- Smoke: `php scripts/aos-permissions-smoke.php`
