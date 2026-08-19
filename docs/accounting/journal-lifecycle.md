# Journal Lifecycle

```text
DRAFT ──submit──► PENDING_APPROVAL (مراجع)
                    │
                    ├──accept──► APPROVED (معتمد، ليس في الأستاذ)
                    │                │
                    └──approve/post──► POSTED (مرحّل = مصدر حقيقة الأستاذ)
                                         │
                                         └──reverse──► original REVERSED + reversal POSTED

Drafts may be deleted. Approved and posted journals cannot be deleted.
Posted journals cannot be edited. Correction = reversal with a required reason.
The existing approve API still posts (draft/reviewed/approved → posted) for backward compatibility.
```

## Draft

View, edit, delete, post, cancel.

## Posted (and legacy approved)

View, print, export, view source, reverse. No edit/delete/cancel.

## Reversed

View, view reversal, view original. No further reverse.

## Reversal

`ReverseJournalEntryAction` copies lines with debit/credit swapped, posts the reversal, sets original `status=reversed`, `reversed_by`, `reversed_at`, `reversal_reason`. Original rows are never deleted.
