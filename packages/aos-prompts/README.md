# AOS Prompt Engine (`dressnmore/aos-prompts`)

**Sprint 7** — Dynamic, provider-agnostic prompt construction for AOS digital employees.

## Purpose

Produce a **Prompt Ready** document that any future AI Provider adapter can send unchanged.

| Does | Does not |
|------|----------|
| Build System + Persona + Context prompts | Call OpenAI / Claude / Gemini |
| Resolve personas & templates | Talk to WhatsApp / Messenger |
| Guard against injection & leakage | Execute Business Tools |
| Validate & optimize sections | Touch Database / Eloquent |
| Version every generated prompt | Contain DressnMore business logic |
| Emit prompt domain events | Expose Controllers / HTTP APIs |

Upstream inputs are **opaque**: planning result arrays, capability/tool identifiers, conversation summary text. Downstream AI Provider adapters consume `PromptDocument::renderedText()`.

## Prompt Pipeline

```
Planning Result
 → Persona Resolver
 → Operating Mode Resolver
 → Tenant Instructions
 → Business Rules
 → Conversation Context
 → Conversation Summary
 → Memory Context (placeholder)
 → Knowledge Context (placeholder)
 → Tool Constraints
 → Safety Policies
 → Localization Rules
 → Formatting Rules
 → Prompt Optimization
 → Prompt Validation
 → Prompt Ready
```

Guard runs first (reject / sanitize). Pipeline short-circuits on rejection.

## Persona Engine

Built-in personas:

| Type | Role |
|------|------|
| Sales Agent | Consultative selling |
| Support Agent | Empathetic problem-solving |
| Reception Agent | Welcome & route |
| Reservation Agent | Slot-focused booking |
| Marketing Agent | Offer-aware messaging |
| Admin Assistant | Structured admin tasks |
| Analytics Assistant | Data-grounded answers |
| Custom | Tenant-defined behavior |

Each persona carries: name, role, tone, communication style, behavior rules, forbidden behaviors, escalation style, decision style, language preferences.

## Prompt Sections

Conceptual ordered sections (provider-agnostic):

System · Persona · Operating Mode · Tenant Instructions · Business Instructions · Safety · Response Constraints · Localization · Formatting · Conversation Context · Conversation Summary · Memory (placeholder) · Knowledge (placeholder) · Planning Result · Capabilities · Tools · Tool Constraints · Current User Message

## Prompt Guard

- Prompt injection detection (EN/AR heuristics)
- Sensitive pattern redaction (card-like / secrets)
- Forbidden instruction detection
- Tenant isolation (reject cross-tenant hints in user message)
- Unsafe prompt rejection

## Prompt Versioning

Every `PromptDocument` includes:

- `version` (engine)
- `created_at`
- `generated_by` (`aos.prompts`)
- `template_version`

## Templates

Greeting · Sales · Support · Complaint · Reservation · Quotation · Invoice · Follow-up · Reminder · Escalation · General Conversation · Unknown Intent

`PromptPolicyResolver` can override template from planning signals / tools when the request uses the general template.

## Extension Points

1. Register additional personas / templates via registries  
2. Replace `PromptGuard` heuristics  
3. Tune `PromptOptimizer` compression thresholds  
4. Swap `PromptTemplateEngine` rendering strategy  
5. Subscribe to prompt domain events  
6. Future Memory / Knowledge injectors replace placeholders without changing the pipeline contract  

## Architecture Decisions

- **Contracts first** — `PromptEngineInterface` is the application port  
- **No AI provider** — rendered text is opaque string for adapters  
- **No DB** — in-memory registries only  
- **Opaque planning** — no hard dependency on `aos-planner` types  
- **Hexagonal / DDD** — domain owns composition; Laravel only wires the module  

## Module

- Provider: `AosPromptsServiceProvider`
- Module: `aos.prompts`
- Feature flag: `prompts`
- Smoke: `php scripts/aos-prompts-smoke.php`
