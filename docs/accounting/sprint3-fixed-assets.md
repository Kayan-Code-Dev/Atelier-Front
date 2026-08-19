# Sprint 3 — Fixed Assets, Depreciation, Equity & Liabilities

The General Ledger remains the single source of truth. Subledgers (`fixed_assets`, `equity_operations`, `liabilities`) are operational only.

## Journal sources

| source_type | source_id | UI |
|---|---|---|
| `fixed_asset` | asset id | `/accounting/assets/{id}` |
| `fixed_asset_disposal` | asset id | `/accounting/assets/{id}` |
| `depreciation` | depreciation run id | `/accounting/assets/depreciation` |
| `equity` | equity operation id | `/accounting/equity` |
| `loan` | liability id | `/accounting/liabilities` |
| `loan_settlement` | liability id | `/accounting/liabilities` |

All postings go through `JournalEntryService::createFromSource()` → existing posting engine.

## Depreciation

Straight-line only. Monthly amount = `(cost - salvage) / months`. Duplicate posting is blocked by unique `idempotency_key` on runs (`{period}:{branch|all}`) and entries (`{assetId}:{period}`).

## Migration

`database/migrations/tenant/2026_08_15_210000_sprint3_fixed_assets_equity_liabilities.php`

Apply with `php artisan tenants:sync-accounting` (migrations + CoA/category/permission seeders).
