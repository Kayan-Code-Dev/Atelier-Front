# Accounting Current State — DressnMore

Date: 2026-08-15  
Scope: tenant backend (`Back-DressnMore-main`) + live tenant FE (`src/features/accounting`)

This document is the Phase 0 audit. Sprint 01 hardening must preserve the data and flows described here.

---

## Current architecture

DressnMore is **database-per-tenant**. Accounting tables live on the tenant connection (`BaseTenantModel::$connection = tenant`). There is **no `tenant_id` column** on accounting tables; isolation is the tenant database bind (`X-Tenant` / tenant middleware). Branch scope is optional `branch_id` on journal headers and lines.

```text
Business modules (payments, expenses, cash movements, PO receive, deposits, returns)
        ↓
JournalEntryPostingService::safePost()   (swallows errors)
        ↓
JournalEntryService::createFromSource()  (writes approved journals)
        ↓
journal_entries + journal_entry_lines
        ↓
AccountingService (GL summary / tree / BS / P&L / ledger)
        ↓
AccountingController + JournalEntryController APIs
        ↓
Tenant FE: /accounting/* and /treasury/entries
```

There is **no** Accounting Event, Accounting Period, audit log, parent CoA, or central posting validator as first-class domain objects.

---

## Existing entities

| Table | Model | Notes |
|---|---|---|
| `accounts` | `Account` | Flat CoA: `code`, `name`, `type`, `is_active`. Types: `asset\|liability\|equity\|revenue\|expense` (lowercase). No parent, no `allow_posting`, no `normal_balance`. |
| `journal_entries` | `JournalEntry` | Header: `entry_number`, `entry_date`, `type`, `source_type`, `source_id`, `reference_number`, `status`, totals, `branch_id`, actors. |
| `journal_entry_lines` | `JournalEntryLine` | `account_id` plus denormalized `account_code` / `account_name`, `debit`, `credit`, `branch_id`, `cost_center_id`. |
| `cashboxes` / `cash_movements` | Treasury | Operational cash, **not** GL. Balances stored on cashbox rows. |

Seeded posting codes (`AccountSeeder`): `1000` الصندوق, `1010` البنك, `1200` العملاء, `1300` المخزون, `2000` الموردون, `2100` ودائع, `3000` رأس المال, `4000–4220` إيرادات, `5000–5200` مصروفات.

---

## Existing relationships

```text
Account 1—* JournalEntryLine *—1 JournalEntry
JournalEntry *—? Branch
JournalEntry *—? User (created_by / approved_by / cancelled_by)
JournalEntry.reversed_entry_id → original JournalEntry (set on the reversal, not the original)
JournalEntryLine *—? Branch
```

Source documents are linked only by (`source_type`, `source_id`). Inverse relation (invoice → journal) is a query, not a FK.

---

## Answers to the audit questions

### 1. Where are journal entries created?

- Manual: `JournalEntryController@store` → `JournalEntryService::create()` (status `draft`).
- Auto: `JournalEntryService::createFromSource()` called from:
  - `JournalEntryPostingService` (payments, expenses, cash movements, deposits, return settlements, supplier payments)
  - `PurchaseOrderService` on receive (inventory Dr / AP Cr)
- Reversal: `JournalEntryService::reverse()` creates a second approved entry.

### 2. Where are journal lines created?

Only `JournalEntryService::syncLines()` — deletes existing lines then inserts. Copies account code/name onto the line.

### 3. How are account balances calculated?

`AccountingService::balancesByAccount()`: `SUM(debit/credit)` of lines whose parent journal `status = approved` and `entry_date` in range. Signed balance:

- asset/expense: debit − credit
- liability/equity/revenue: credit − debit

Uses PHP `round(..., 2)` / float casts, not bcmath.

### 4. How is the Balance Sheet calculated?

`AccountingService::balanceSheet($asOf)` groups those balances by type. Adds synthetic equity line `3999 صافي الدخل (غير الموزع)` = YTD revenue − expense so Assets ≈ Liabilities + Equity.

### 5. How is the Income Statement calculated?

Same balance helper restricted to `[from, to]`, types revenue vs expense, `net_income = revenue − expense`.

### 6. How does Treasury relate to journals?

- Cashboxes remain the operational source of cash UI (`/cashboxes`).
- `CashMovementService` posts a GL journal via `postFromCashMovement` unless the movement already references expense / invoice payment / supplier payment / deposit (to avoid double posting).
- Summary still returns `cashbox_balances` plus GL totals.
- Ledger without `account_id` still returns the **old cash reconstruction** (payments + expenses), not GL.

### 7. Is the same balance calculated in more than one place?

Yes:

- `AccountingService` (summary, tree, BS, P&L, GL ledger)
- `JournalEntryService::computeTotals` (header totals, float)
- `JournalEntryService::summary` (list KPIs)
- Cashbox `current_balance` (treasury, independent)
- Report tabularizer reads whatever `AccountingService::summary` returns

### 8. Can a posted (approved) entry be edited or deleted?

- **Edit:** blocked (`assertEditable` rejects approved/cancelled).
- **Delete:** no delete API; **cancel is allowed on approved** — this mutates a posted journal instead of reversing it.
- **Reverse:** creates a new approved reversal; **does not mark the original as reversed**.

### 9. How are journals numbered?

Server-side `JE-{Ymd}-{seq:04d}` in `generateEntryNumber()`. Unique per tenant DB. Not locked (`lockForUpdate`); race possible. Frontend does not send the number.

### 10. How is the source set?

`source_type` + `source_id` + `reference_number`. Types: `manual`, `invoice`, `payment`, `expense`, `return`, `purchase_order`, `supplier_payment`, `cash_movement`, `system`, `security_deposit_collection`, `rental_return_settlement`. Idempotent `findBySource` skips duplicates.

### 11. How are tenant_id and branch_id applied?

- Tenant: connection isolation only. Request `tenant_id` is not used.
- Branch: nullable on header and lines. GL filters `journal_entries.branch_id` when provided. Accounts are global (no account.branch_id).

### 12. What existing data must be kept?

All tenant `accounts`, `journal_entries`, `journal_entry_lines`, cashbox balances, and auto-posting source links. Do **not** renumber `1000` الصندوق into a parent (that would break live postings). Parent CoA must use **new** codes (`1`–`5`).

---

## Current posting flow

```text
Draft (manual) → approve() → status=approved  (this is today's "posted")
Source modules → createFromSource() → status=approved immediately
Cancel → status=cancelled (allowed on draft and approved)
Reverse → new approved entry, original stays approved
```

Approval is the posting step. There is no `pending_approval` or `posted` status in production data.

`safePost()` **logs and swallows** failures, so operational cash can succeed while GL is missing.

---

## Current reporting calculation

- Default summary period: year.
- BS as-of date; IS period.
- Only `approved` journals hit reports (cancelled/draft excluded).
- Reports module `reports.accounting` uses the same summary payload (`total_income` aliased from GL revenue).

---

## Current Treasury integration

Keep as-is for this sprint. Accounting Core must ingest treasury-originated journals through the posting service. Do not rewrite cashboxes, movements, or cashbox balances.

---

## Current permissions

Existing keys:

- `accounting.view`
- `accounting.journal_entries.{view,create,update,approve,cancel,reverse,export}`
- `reports.accounting`

`CheckTenantPermission` aliases journal view/export to `accounting.view`. Owner role gets every `PermissionLabels` key.

---

## Existing problems

1. No single posting gateway — PO receive writes journals directly.
2. Float rounding in totals and balances.
3. Approved journals can be cancelled (not immutable).
4. Reverse does not stamp the original as reversed.
5. Number generator not concurrency-safe.
6. Flat CoA; posting on any active account; no parent/posting flag.
7. No closed-period control.
8. No audit trail table.
9. Duplicate balance math (reports vs ledger vs journal totals).
10. `safePost` can silently skip GL.
11. Line still duplicates account code/name.
12. Cash ledger fallback diverges from GL.
13. Status vocabulary (`approved`) ≠ domain `posted`.

---

## Migration strategy

1. Additive schema only (new columns/tables). No drops.
2. Keep `entry_number`, `approved` status, `reversed_entry_id`, denormalized line names.
3. Treat **both** `approved` and `posted` as posted for GL.
4. New posts write `posted`. Existing rows stay `approved`.
5. Parent accounts `1`–`5`; existing `1000+` remain posting accounts.
6. Unbalanced headers: `needs_review=true`, never auto-balance.
7. Empty `accounting_periods` means all dates are open.
8. Wire modules through `AccountingPostingService` without changing their business rules.

---

## Risks

- Changing approve status to `posted` requires FE status labels.
- Disallowing cancel-on-approved changes current API behavior (correct, but callers must reverse).
- Tightening `allow_posting` could block a journal if a parent is used — mitigate by keeping current codes posting-enabled.
- `safePost` swallowing errors: Sprint 01 keeps the wrapper for treasury compatibility but posts through the core inside it.
- PHPUnit locally may fail on PHP 8.2; project requires PHP 8.3 (run tests on 8.3).
