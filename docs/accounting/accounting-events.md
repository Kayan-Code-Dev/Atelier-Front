# Accounting Events

Modules must not invent debit/credit in controllers. They emit an `AccountingEvent` (payload + source) and Accounting builds lines.

```text
Business Transaction
    → AccountingEvent (persisted)
    → Journal builder (account codes)
    → JournalEntryValidator
    → AccountingPostingService
    → Posted JournalEntry
```

Sprint 01 event types (aligned with existing source_type values):

- `payment` / InvoicePaid
- `expense`
- `cash_movement` / TreasuryTransfer
- `supplier_payment`
- `purchase_order`
- `security_deposit_collection`
- `rental_return_settlement`
- `manual`
- `reversal`

`JournalEntryPostingService` remains the compatibility facade for Treasury/Sales/Expenses. Internally it now records an event and posts through `AccountingPostingService`.
