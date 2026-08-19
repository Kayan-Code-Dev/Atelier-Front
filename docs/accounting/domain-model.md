# Accounting Domain Model

The General Ledger is the single source of truth for accounting balances. All financial transactions must eventually produce balanced journal entries, and all accounting reports must derive their values from the posted ledger. No report or balance may be manually mutated.

## Isolation

Tenant isolation is the tenant database connection. Accounting entities do not store `tenant_id`. Branch accounting uses nullable `branch_id`. Never accept tenant identity from the frontend.

## Aggregates

### Account

Chart of accounts node.

| Field | Notes |
|---|---|
| code | Unique per tenant |
| name | Display |
| type | `asset`, `liability`, `equity`, `revenue`, `expense` (DB lowercase; domain ASSET…) |
| parent_id | Null for type roots (`1`–`5`) |
| normal_balance | `debit` for asset/expense, `credit` otherwise |
| is_active | Inactive accounts cannot be posted to |
| is_system | Seeded / protected codes |
| allow_posting | False on parent groups |
| branch_id | Optional; unused on global CoA |

### JournalEntry

Official accounting record. Numbered `JE-YYYYMMDD-000001` (legacy width 4 is accepted; new numbers use 6 digits when sequence exceeds 9999, otherwise keep 4-digit compatibility `0001`).

Statuses (stored lowercase):

| Status | Meaning |
|---|---|
| draft | Editable |
| pending_approval | Reserved; unused while approval is optional |
| approved | Legacy posted (existing rows) |
| posted | Posted (new rows) |
| reversed | Original after reversal |
| cancelled | Draft/pending void only |

`isPosted()` = `approved` OR `posted`.

### JournalEntryLine

Belongs to one journal. `debit` XOR `credit`. `account_code`/`account_name` kept as historical snapshot; validation uses `account_id`.

### AccountingPeriod

Optional closed window (`starts_on`, `ends_on`, `is_closed`). If no period covers a date, posting is allowed.

### AccountingEvent

Persisted intent from a business transaction before/while posting. Modules emit events; Accounting decides debit/credit.

### AccountingSource

Logical (`source_type`, `source_id`, `source_reference`). Not a separate table in Sprint 01.

## Money

All accounting math uses `AccountingMoney` (bcmath, scale 2). Database columns stay `DECIMAL(14,2)` (existing) which is compatible with the requested `DECIMAL(18,2)` precision for current amounts.
