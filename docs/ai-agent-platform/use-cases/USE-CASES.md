# AI Agent Use Cases — Master Overview

**Document type:** System Behavior Specification  
**Platform:** DressnMore AI Agent Platform (Digital Employee OS)  
**Version:** 1.0  

## Group map

| Group ID | Group | Focus |
|----------|-------|-------|
| G01 | Conversation Hygiene | Greeting, goodbye, small talk, silence, return after long gap |
| G02 | Atelier Knowledge | Hours, location, policies, payment methods, seasonal offers |
| G03 | Catalog & Availability | Products/dresses, sizes, availability, media about items |
| G04 | Sales & Service Inquiry | Rental, tailoring, pricing, unsupported services |
| G05 | Appointments & Fittings | Book, confirm, reschedule, cancel fittings/appointments |
| G06 | Orders & Tracking | Order status, cancel order request, confirm order |
| G07 | Invoices & Balance | Invoice copy, outstanding balance, deposits |
| G08 | Payments | Payment methods, transfer receipt, payment proof |
| G09 | Delivery & Pickup | Delivery timing, postpone pickup/delivery, location share |
| G10 | Complaints & Exceptions | Complaints, discounts, exceptions, anger |
| G11 | Identity & Data Capture | Customer data, phone change |
| G12 | Media Intake | Image, voice, PDF handling |
| G13 | Human Collaboration | Explicit human request, staff takeover, return to AI |
| G14 | Automation Lifecycle | No-reply follow-up, SLA, reminders |
| G15 | Administration | Agent policy/knowledge updates (staff-side behaviors affecting agent) |

Detailed specs: [catalog/](./catalog/).

## Actor glossary (behavior docs)

| Actor | Meaning |
|-------|---------|
| **Customer** | External Contact messaging via a Channel |
| **AI Agent** | Digital Employee for the Tenant |
| **Human Staff** | Tenant user (sales/support/admin roles as applicable) |
| **Sales** | Human Staff with sales responsibility |
| **Admin** | Atelier admin configuring agent/policy/knowledge |
| **System** | Platform automation, timers, ingress, audit emitters |

## Standard decision path (all UCs)

1. Channel → Ingress → Tenant Resolver (binding)  
2. Conversation Manager + State Machine  
3. Intent detection → Context Bundle  
4. Permission check → Tool / Reply / Clarify / Approve / Escalate  
5. Audit + Analytics events  

## Coverage index (scenario → UC)

| Real-world scenario | UC ID |
|---------------------|-------|
| Ask prices | UC-SALES-01 |
| Book fitting/appointment | UC-APPT-01 |
| Reschedule appointment | UC-APPT-03 |
| Cancel appointment | UC-APPT-04 |
| Confirm booking | UC-APPT-02 |
| Order status | UC-ORD-01 |
| Outstanding balance | UC-INV-02 |
| Invoice copy | UC-INV-01 |
| Send location | UC-KNOW-02 |
| Working hours | UC-KNOW-01 |
| Talk to human | UC-HUM-01 |
| Complaint | UC-CMP-01 |
| Request discount | UC-CMP-02 |
| Unsupported service | UC-SALES-04 |
| Dress photo | UC-MED-01 |
| Voice note | UC-MED-02 |
| PDF | UC-MED-03 |
| Customer silent | UC-HYG-04 |
| Returns after long gap | UC-HYG-05 |
| Postpone delivery/pickup | UC-DEL-02 |
| Available products | UC-CAT-01 |
| Rent a dress | UC-SALES-02 |
| Tailoring | UC-SALES-03 |
| Required sizes | UC-CAT-03 |
| Send personal data | UC-ID-01 |
| Change phone | UC-ID-02 |
| Cancel order | UC-ORD-02 |
| Cancellation policy | UC-KNOW-03 |
| Payment methods | UC-PAY-01 |
| Transfer receipt | UC-PAY-02 |
| Seasonal offers | UC-KNOW-04 |
| Greeting / goodbye / small talk | UC-HYG-01..03 |
| Delivery inquiry | UC-DEL-01 |
