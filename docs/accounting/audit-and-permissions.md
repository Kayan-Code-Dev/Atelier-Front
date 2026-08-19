# Audit and Permissions

## Audit trail

Table `accounting_audit_logs`:

- `user_id`, `action`, `entity_type`, `entity_id`, `metadata`, timestamps

Actions: `created`, `updated`, `submitted`, `approved`, `posted`, `reversed`, `cancelled`, `deleted`.

Journal details expose `audit_timeline` from this table (plus header timestamps as fallback).

## Permissions

New keys (seeded via `PermissionLabels`; owner receives all):

- `accounting.accounts.view|create|update|deactivate`
- `accounting.journals.view|create|update_draft|delete_draft|post|approve|reverse`
- `accounting.reports.view|export`

Legacy keys remain:

- `accounting.journal_entries.*`
- `accounting.view`
- `reports.accounting`

`CheckTenantPermission` aliases map new journal keys to the old `journal_entries.*` keys so existing roles keep working.

There is no `is_admin` bypass for accounting.
