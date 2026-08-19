# Intent Analyzer

`IntentAnalyzer` maps message text → `AnalyzedIntent` via `PlatformIntentCatalog` keywords (Arabic + English).

## Examples

| Message | Intent |
|---------|--------|
| احجز فستان | BookReservation |
| أضف عميل | CreateCustomer |
| كم مبيعات اليوم | SalesSummary |

## Behavior

- No LLM in this sprint
- Highest-confidence keyword rule wins
- Conflicting write intents (book + cancel) → unknown/conflicting (`known=false`)
- Empty signals → `AnalyzedIntent::unknown()`

Each rule carries: `toolPlan`, `capabilities`, optional `policy` / `approval`.
