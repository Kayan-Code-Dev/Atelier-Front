# Contracts — Knowledge & Atelier Settings

---

## SearchKnowledge

| Field | Content |
|-------|---------|
| Tool Name | `SearchKnowledge` |
| Business Purpose | Retrieve published atelier knowledge snippets |
| Description | Semantic/keyword retrieval over Knowledge Collections |
| Business Intent(s) | AskCancellationPolicy, AskWorkingHours, UnsupportedService, AskSizeGuide |
| Required Capabilities | `read_knowledge` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | Isolation Key; language |
| Expected Inputs | query; collectionHints?; limit |
| Expected Output | snippets[{docRef, text, citation}] |
| Possible Outcomes | Hits / Empty |
| Failure Scenarios | Index unavailable |
| Validation Rules | Published docs only |
| Approval Requirements | None |
| Human Escalation Rules | Empty on critical policy → Escalate |
| Audit Events | ToolExecuted |
| Analytics Events | KnowledgeSearch |
| Business Rules | No draft docs |
| Security Considerations | Tenant-scoped index |
| Idempotency Rules | Read |
| Concurrency Considerations | — |
| Side Effects | None |
| Dependencies | Knowledge provider |
| Related Tools | SearchFAQ, SearchPolicies |
| Versioning Notes | v1 |

---

## SearchFAQ

| Field | Content |
|-------|---------|
| Tool Name | `SearchFAQ` |
| Business Purpose | Answer frequent questions from FAQ collection |
| Description | Specialized knowledge search |
| Business Intent(s) | Greeting follow-ups, common asks |
| Required Capabilities | `read_knowledge` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | Language |
| Expected Inputs | query |
| Expected Output | faq hits |
| Possible Outcomes | Hits / Empty |
| Failure Scenarios | — |
| Validation Rules | Published only |
| Approval Requirements | None |
| Human Escalation Rules | — |
| Audit Events | ToolExecuted |
| Analytics Events | FaqSearch |
| Business Rules | Prefer short answers |
| Security Considerations | — |
| Idempotency Rules | Read |
| Concurrency Considerations | — |
| Side Effects | None |
| Dependencies | Knowledge |
| Related Tools | SearchKnowledge |
| Versioning Notes | v1 |

---

## SearchPolicies

| Field | Content |
|-------|---------|
| Tool Name | `SearchPolicies` |
| Business Purpose | Retrieve cancellation/payment/service policies |
| Description | Policy-focused retrieval |
| Business Intent(s) | AskCancellationPolicy, CancelAppointment, CancelOrder |
| Required Capabilities | `read_knowledge` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | — |
| Expected Inputs | policyType?; query |
| Expected Output | policy snippets + citations |
| Possible Outcomes | Hits / Empty |
| Failure Scenarios | Missing policy docs |
| Validation Rules | Published |
| Approval Requirements | None |
| Human Escalation Rules | Empty → Escalate (do not invent policy) |
| Audit Events | ToolExecuted |
| Analytics Events | PolicySearch |
| Business Rules | Ground replies on citations |
| Security Considerations | — |
| Idempotency Rules | Read |
| Concurrency Considerations | — |
| Side Effects | None |
| Dependencies | Knowledge |
| Related Tools | SearchKnowledge |
| Versioning Notes | v1 |

---

## GetAtelierSettings / GetBusinessHours / GetBranchLocation

| Field | Content |
|-------|---------|
| Tool Name | `GetAtelierSettings` · `GetBusinessHours` · `GetBranchLocation` |
| Business Purpose | Expose hours, branches, payment methods published in settings |
| Description | Settings reads (specialized facades allowed) |
| Business Intent(s) | AskWorkingHours, AskLocation, AskPaymentMethods |
| Required Capabilities | `read_atelier_settings` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | Timezone |
| Expected Inputs | branchId? |
| Expected Output | hours / address / paymentMethods / branches |
| Possible Outcomes | Found / Incomplete |
| Failure Scenarios | Unconfigured |
| Validation Rules | — |
| Approval Requirements | None |
| Human Escalation Rules | Incomplete → Escalate |
| Audit Events | ToolExecuted |
| Analytics Events | SettingsRead |
| Business Rules | Do not invent addresses/accounts |
| Security Considerations | No private bank full details beyond published |
| Idempotency Rules | Read |
| Concurrency Considerations | — |
| Side Effects | None |
| Dependencies | Settings port |
| Related Tools | SearchKnowledge |
| Versioning Notes | v1 facades |
