# Operating Modes

Mode is a **runtime overlay** on the Permission Engine. Changing mode never silently grants denied capabilities.

## Assistant Mode

| Aspect | Behavior |
|--------|----------|
| Customer send | AI prepares replies/actions for staff; no autonomous customer send (or send only after approval) |
| Tools | May dry-run or propose; execution often requires approval |
| Best for | Onboarding, training, high-control ateliers |
| Advantage | Maximum control and quality |

## Hybrid Mode (recommended default)

| Aspect | Behavior |
|--------|----------|
| Simple informational intents | AI answers within permissions |
| Complex / financial / ambiguous | Human Handover |
| Best for | Most ateliers |
| Advantage | Cost/quality balance |

## Full Auto Mode

| Aspect | Behavior |
|--------|----------|
| Conversation | AI owns end-to-end within permissions |
| Escalation | Only on policy break, explicit human request, or safety events |
| Best for | Mature KB + tight ceilings |
| Advantage | Highest containment, lowest staff load |

## Mode selection guidance

1. Wizard default: **Hybrid**  
2. First 48 hours after go-live: prefer Hybrid + aggressive handover  
3. Promote to Full Auto only after containment/quality gates in Analytics  
4. Demote to Assistant instantly if abuse or repeated policy violations  
