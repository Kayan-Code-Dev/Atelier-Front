# Posting Rules

Central class: `App\Accounting\JournalEntryValidator`.

All posted journals must pass these rules. Drafts may be unbalanced; posting may not.

1. At least one line (API still requires two lines so a journal can balance).
2. Every line has a real `account_id`.
3. Account is active.
4. Account `allow_posting = true` (parents rejected).
5. A line cannot have debit > 0 and credit > 0.
6. A line cannot be 0/0.
7. SUM(debit) = SUM(credit) using `AccountingMoney`.
8. Totals > 0.
9. `entry_date` is not inside a closed `AccountingPeriod`.
10. Tenant isolation via tenant connection only.
11. If `branch_id` is set, it must exist on the tenant.

Posted journals are immutable: no edit, delete, date/account/amount/description/source change. Correction = reversal.

Numbering is server-side, unique per tenant, never reused.

`AccountingPostingService` is the only writer of posted journals. Controllers and other modules must not `JournalEntry::create` with posted/approved status.
