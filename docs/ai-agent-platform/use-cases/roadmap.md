# Use Cases Implementation Roadmap

Ordered by **business value** (containment, revenue assist, risk reduction), aligned with Architecture phases — not by easiest engineering.

## MVP (first pilot value)

Must prove Digital Employee usefulness with low financial risk.

- UC-HYG-01 Greeting  
- UC-HYG-02 Goodbye  
- UC-KNOW-01 Hours  
- UC-KNOW-02 Location  
- UC-KNOW-03 Cancellation policy (read)  
- UC-KNOW-04 Seasonal offers (published only)  
- UC-PAY-01 Payment methods  
- UC-SALES-01 Ask prices (published)  
- UC-SALES-04 Unsupported service  
- UC-CAT-01 Availability list (read)  
- UC-ORD-01 Track order (read)  
- UC-INV-02 Ask balance (read)  
- UC-HUM-01 Request human  
- UC-CMP-01 Complaint → escalate  
- UC-HYG-03 SmallTalk (light)

**Exit:** Hybrid agent answers atelier FAQs + status/balance/availability and escalates complaints/human asks.

## Phase 1 (Architecture Phase 1 vertical slice completion)

- UC-CAT-02 Item availability  
- UC-CAT-03 Size guide  
- UC-INV-01 Invoice copy (summary)  
- UC-DEL-01 Delivery status  
- UC-HYG-04 Silence follow-up  
- UC-HYG-05 Returning customer  
- UC-SALES-02 Rent qualify (no hard commit)  
- UC-SALES-03 Tailoring qualify → suggest appointment  
- UC-MED-01 Image ack + clarify  
- UC-CMP-02 Discount → escalate (no apply)  
- UC-PAY-02 Payment proof ack + staff Task  
- UC-AUTO-01 Appointment reminder (if schedule read exists)

**Exit:** Matches Architecture Phase 1 — read tools + handover + wizard-activated WhatsApp.

## Phase 2 (write tools + approvals)

- UC-APPT-01 Book appointment (with caps)  
- UC-APPT-02 Confirm  
- UC-APPT-03 Reschedule  
- UC-APPT-04 Cancel appointment (approved)  
- UC-DEL-02 Postpone delivery (approved)  
- UC-ID-01 Capture customer data  
- UC-MED-02 Voice (transcription)  
- UC-MED-03 PDF routing  
- UC-HUM-02 Staff return to AI  
- UC-ADM-01 Knowledge publish effects  
- UC-ADM-02 Policy/mode changes  
- Bounded `create_booking` / `reschedule_*` under Approval

**Exit:** Safe writes with Approval Engine; measurable containment uplift.

## Phase 3 (multi-channel + automation depth)

- Same UCs across Web Chat / Messenger / Instagram adapters  
- UC-AUTO-02 Feedback loop  
- Richer rental hold flows  
- Cross-channel Contact identity  
- Stronger catalog matching from images  

**Exit:** Channel-agnostic behavior parity for MVP+Phase1/2 UCs.

## Enterprise

- UC-ORD-02 Cancel order (strict approval matrices)  
- UC-ID-02 Phone change with verification playbooks  
- Controlled `ApplyDiscount` within ceilings  
- Full Auto for high-confidence read + selected bookings  
- Advanced analytics outcomes (`Sale`, quality rubrics)  
- VIP policies, branch playbooks, multi-branch scheduling  

**Exit:** Sellable Enterprise digital employee with governed autonomy.
