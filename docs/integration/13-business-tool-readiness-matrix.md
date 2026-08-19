# 13 — Business Tool Readiness Matrix

Status meanings:

- **Available**: Contract documented in Business Tools pack  
- **Missing**: Needed for atelier ops but not yet contracted  
- **Ready**: Contract + AOS Gateway can host it (adapter pending)  
- **Blocked**: Waiting on policy/product decision  
- **Future**: Explicitly deferred  

| Domain Module | Available (contracts) | Missing | Ready for adapter work | Blocked | Future |
|---------------|----------------------|---------|------------------------|---------|--------|
| **Customers** | Get/Search/Upsert/Notes/UpdatePhone | Bulk merge UX tools | Yes | KYC edge cases | — |
| **Leads / Marketing** | Create/Update LeadNote | Campaign send tools | Lead notes yes | Campaign tooling ownership | Campaign execution via Workflow |
| **Reservations** | Find/Get/List/Create/Reschedule/Cancel | Waitlist tools | Yes | Fee dispute policy | — |
| **Orders** | Status/List/Cancel | CreateOrder from chat (partial via quotation/rental hold) | Status/Cancel yes | Auto-create order rules | Broader order mutations |
| **Tailoring** | Measurements + lead times | Workshop floor tasking | Yes | Factory internal ops | Staff-only factory tools |
| **Rental** | Hold + availability + catalog | Damage/deposit tools | Yes | Deposit accounting rules | — |
| **Inventory** | Search/Check/Availability | Warehouse transfers | Read yes | Stock adjustment by AI | Write stock |
| **Invoices** | Get/List/Send | Create invoice from chat | Read/Send yes | Fiscal constraints | — |
| **Payments** | Balance/Proof/Link/MarkPaid | Refunds | Partial | MarkPaid always HITL | Refunds |
| **Accounting** | — (internal) | Agent ledger posts | — | Must stay internal | Never expose raw GL |
| **Cashbox** | — | Agent cash sessions | — | High risk | Staff-only |
| **HR** | — | Attendance/payroll tools | — | Out of digital employee scope | Optional staff assistant pack |
| **Reports** | Analytics events (auto) | Ad-hoc report tools | Events ready | — | Executive report tools |
| **Analytics** | Gateway analytics hooks | — | Yes | — | Custom KPI tools |
| **Settings** | Hours/Location/Atelier settings | Write settings | Read ready | Write settings by AI | Controlled writes |
| **Knowledge** | Search FAQ/Policies/Knowledge | Write knowledge from chat | Read ready | Publish rights | Authoring via Workspace not Tools |
| **Notifications** | NotifyStaff/Customer | Broadcast campaigns | Yes | Opt-in/legal | — |
| **AI / Conversation Ops** | Memory/Summary/Close/Transfer/Approval/Transcribe | — | Yes | — | — |
| **Utilities** | Image match / STT | Translation tool | Partial | Vendor choice | — |

## MVP binding recommendation (no redesign)

Implement adapters first for:

1. Customers (read/search)  
2. Reservations (find/create/reschedule)  
3. Orders (status)  
4. Invoices (read/send)  
5. Knowledge (search)  
6. TransferConversation + RequestApproval  
7. NotifyStaff  

Everything Critical (`UpdateCustomerPhone`, `CancelOrder`, `MarkInvoicePaid`, `ApplyDiscount`) stays **Always Approval**.
