# Contracts — Conversation Operations

---

## StoreConversationMemory

| Field | Content |
|-------|---------|
| Tool Name | `StoreConversationMemory` |
| Business Purpose | Persist working memory slices for Context Engine |
| Description | Updates Conversation Memory facts/summary fragments |
| Business Intent(s) | Cross-cutting after tools/messages |
| Required Capabilities | `write_conversation_memory` (system/agent internal) |
| Required Permissions | Internal agent capability |
| Allowed Modes | A, H, F |
| Required Context | conversationRef |
| Expected Inputs | conversationRef; memoryPatch; schemaVersion |
| Expected Output | memoryVersion |
| Possible Outcomes | Stored / Rejected |
| Failure Scenarios | Closed conversation |
| Validation Rules | Size limits; no raw secrets |
| Approval Requirements | None |
| Human Escalation Rules | None |
| Audit Events | Optional (may be sampled) |
| Analytics Events | MemoryUpdated |
| Business Rules | Memory ≠ source of financial truth |
| Security Considerations | Redact PII per policy |
| Idempotency Rules | Versioned patches |
| Concurrency Considerations | Version check |
| Side Effects | Memory updated |
| Dependencies | Conversation aggregate |
| Related Tools | GenerateConversationSummary |
| Versioning Notes | v1 |

---

## GenerateConversationSummary / SummarizeConversation

| Field | Content |
|-------|---------|
| Tool Name | `GenerateConversationSummary` (alias `SummarizeConversation`) |
| Business Purpose | Produce Summary for handover/resolve/analytics |
| Description | Creates Conversation Summary artifact |
| Business Intent(s) | EscalateHuman, Goodbye, Complaint |
| Required Capabilities | `summarize_conversation` |
| Required Permissions | Internal/agent |
| Allowed Modes | A, H, F |
| Required Context | Messages + tool results |
| Expected Inputs | conversationRef; purpose (handover/close) |
| Expected Output | summaryText; structured bullets |
| Possible Outcomes | Summarized |
| Failure Scenarios | Empty conversation |
| Validation Rules | — |
| Approval Requirements | None |
| Human Escalation Rules | — |
| Audit Events | ConversationSummarized |
| Analytics Events | SummaryGenerated |
| Business Rules | Must not invent tool facts |
| Security Considerations | Redaction |
| Idempotency Rules | Purpose-keyed supersede |
| Concurrency Considerations | — |
| Side Effects | Summary stored |
| Dependencies | Memory/Messages |
| Related Tools | TransferConversation, CloseConversation |
| Versioning Notes | v1 |

---

## CloseConversation

| Field | Content |
|-------|---------|
| Tool Name | `CloseConversation` |
| Business Purpose | Move Conversation to Resolved/Closed |
| Description | Terminal lifecycle transition with outcome |
| Business Intent(s) | Goodbye, SupportSolved paths |
| Required Capabilities | `close_conversation` |
| Required Permissions | Agent/staff |
| Allowed Modes | A (staff confirm often) · H · F |
| Required Context | No blocking PendingApproval / active critical handover unresolved per policy |
| Expected Inputs | conversationRef; outcome; reason? |
| Expected Output | status Closed/Resolved |
| Possible Outcomes | Closed / BlockedByPendingWork |
| Failure Scenarios | Open approval; ActiveHuman without staff |
| Validation Rules | Outcome enum |
| Approval Requirements | Optional |
| Human Escalation Rules | If pending human work |
| Audit Events | ConversationClosed |
| Analytics Events | ConversationClosed |
| Business Rules | Idempotent close |
| Security Considerations | — |
| Idempotency Rules | Yes |
| Concurrency Considerations | State machine guard |
| Side Effects | State change; summary optional |
| Dependencies | GenerateConversationSummary optional |
| Related Tools | CreateFollowUp |
| Versioning Notes | v1 |

---

## MatchCatalogByImage *(optional / later)*

| Field | Content |
|-------|---------|
| Tool Name | `MatchCatalogByImage` |
| Business Purpose | Suggest catalog items from customer image |
| Description | Vision match returning candidates |
| Business Intent(s) | SendImage |
| Required Capabilities | `match_catalog_image` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | mediaRef |
| Expected Inputs | mediaRef; limit |
| Expected Output | candidates[] |
| Possible Outcomes | Matches / LowConfidence / Unsupported |
| Failure Scenarios | No vision provider |
| Validation Rules | Media type image |
| Approval Requirements | None |
| Human Escalation Rules | LowConfidence styling |
| Audit Events | ToolExecuted |
| Analytics Events | ImageMatch |
| Business Rules | Suggestions need availability check |
| Security Considerations | Unsafe image handling |
| Idempotency Rules | Read-like |
| Concurrency Considerations | — |
| Side Effects | None |
| Dependencies | Media + catalog |
| Related Tools | SuggestProducts, CheckItemAvailability |
| Versioning Notes | v1 optional Phase 3 |

---

## TranscribeAudio *(optional platform utility exposed as Tool)*

| Field | Content |
|-------|---------|
| Tool Name | `TranscribeAudio` |
| Business Purpose | Convert voice note to text for intent pipeline |
| Description | STT utility |
| Business Intent(s) | SendVoiceNote |
| Required Capabilities | `transcribe_audio` |
| Required Permissions | Policy grant |
| Allowed Modes | A, H, F |
| Required Context | mediaRef |
| Expected Inputs | mediaRef; languageHint? |
| Expected Output | transcript; confidence |
| Possible Outcomes | Transcribed / Unsupported / Failed |
| Failure Scenarios | No STT |
| Validation Rules | Audio type |
| Approval Requirements | None |
| Human Escalation Rules | Unsupported → ask text / human |
| Audit Events | ToolExecuted |
| Analytics Events | AudioTranscribed |
| Business Rules | Low transcript conf → Clarify |
| Security Considerations | Media retention policy |
| Idempotency Rules | Same media → cached transcript allowed |
| Concurrency Considerations | — |
| Side Effects | May store transcript on Message |
| Dependencies | STT provider port |
| Related Tools | — |
| Versioning Notes | v1 optional |
