# Permission Engine — Capability Firewall

## Purpose

Define and enforce what the digital employee may do inside a tenant atelier. Deny by default. Mode overlays permissions; it does not replace them.

## Capability classes

### Typically allow-by-config

- Reply to customers
- Create booking / delivery appointment
- Reschedule appointment
- Create invoice / order (bounded)
- Read balance / deposit
- Read order status
- Search catalog / availability

### Deny-by-default examples

- Delete customers / invoices / records
- Refund money
- Change base prices
- Grant discounts above ceiling
- Change user roles/permissions
- Access advanced accounting / payroll unless explicitly enabled

## Decision matrix

```
Action + Mode + Capability Policy + Amount Thresholds + Customer Risk
  → allow | deny | require_human_approval
```

## Enforcement points

1. Before a tool appears in the Orchestrator’s available schema  
2. Before execution in the Tool Gateway  
3. Before a secret/sensitive field enters Context  
4. On every Deny — write Audit  

## Relationship to Entitlements

Plan feature `ai_agent` (conceptual) can disable the whole platform or cap channels/messages. Entitlements gate **availability**; owner policy gates **capability**.

## Relationship to operating modes

| Mode | Permission overlay |
|------|--------------------|
| Assistant | Even allowed tools may require human send/approval |
| Hybrid | Simple allowed intents auto; complex → escalate |
| Full Auto | Allowed tools execute within ceilings; escalate only on policy break |

See [operating-modes.md](./operating-modes.md).
