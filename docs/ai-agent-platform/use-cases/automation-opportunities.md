# Automation Opportunities

Rating per Use Case: **Full** (AI/System end-to-end within policy) · **Hybrid** (AI + human gates) · **Human** (AI support only / immediate handover).

| UC | Rating | Notes |
|----|--------|-------|
| UC-HYG-01 Greeting | Full | |
| UC-HYG-02 Goodbye | Full / Hybrid | Hybrid if open tasks |
| UC-HYG-03 SmallTalk | Full | |
| UC-HYG-04 Silence | Full | Workflow |
| UC-HYG-05 Return gap | Hybrid | Refresh facts; conflict → human |
| UC-KNOW-01 Hours | Full | |
| UC-KNOW-02 Location | Full | |
| UC-KNOW-03 Cancel policy | Full / Hybrid | Dispute → human |
| UC-KNOW-04 Offers | Full / Hybrid | Custom discount → human |
| UC-CAT-01 Availability list | Full | |
| UC-CAT-02 Item availability | Full | |
| UC-CAT-03 Size guide | Full / Hybrid | Deep consult hybrid |
| UC-SALES-01 Prices | Full / Hybrid | Negotiation hybrid |
| UC-SALES-02 Rent dress | Hybrid | Commit/hold gated |
| UC-SALES-03 Tailoring | Hybrid | |
| UC-SALES-04 Unsupported | Full | |
| UC-APPT-01 Book | Hybrid / Full | Full only if create_booking + High conf |
| UC-APPT-02 Confirm | Full | |
| UC-APPT-03 Reschedule | Hybrid | |
| UC-APPT-04 Cancel appt | Hybrid | |
| UC-ORD-01 Track | Full | |
| UC-ORD-02 Cancel order | Human | Default |
| UC-INV-01 Invoice | Full / Hybrid | Doc send may approve |
| UC-INV-02 Balance | Full | Dispute human |
| UC-PAY-01 Methods | Full | |
| UC-PAY-02 Payment proof | Hybrid / Human | Settle human |
| UC-DEL-01 Delivery status | Full | |
| UC-DEL-02 Postpone | Hybrid | |
| UC-CMP-01 Complaint | Human | |
| UC-CMP-02 Discount | Human / Hybrid | Published-only full |
| UC-CMP-03 Exception/manager | Human | |
| UC-ID-01 Customer data | Hybrid | |
| UC-ID-02 Phone change | Human | |
| UC-MED-01 Image | Hybrid | |
| UC-MED-02 Voice | Hybrid | |
| UC-MED-03 PDF | Hybrid / Human | |
| UC-HUM-01 Request human | Human | |
| UC-HUM-02 Staff ownership | Human/System | |
| UC-AUTO-01 Reminder | Full | |
| UC-AUTO-02 Feedback | Full / Human on negative | |
| UC-ADM-* | Admin/System | Not customer Full Auto |

## Mode overlay

- **Assistant:** treat Full as “draft for staff” unless explicitly allowed to send.  
- **Hybrid:** follow table.  
- **Full Auto:** still Human for CancelOrder, remedies, phone change, unpaid→paid, safety.
