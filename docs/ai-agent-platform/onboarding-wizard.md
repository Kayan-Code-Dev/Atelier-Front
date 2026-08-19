# First-Time Channel Wizard (WhatsApp) — Conceptual

No UI wireframes. This is the activation journey that produces an Agent Profile + live Channel Binding.

## Steps

1. **Connect Number** — Link WABA / number and verify ownership  
2. **Business Profile** — Activity type, city, branches, hours  
3. **Atelier Facts** — Services (rental / tailoring / sales), core policies  
4. **Train Persona** — Name, tone, language, sample replies, forbidden phrases  
5. **Permissions** — Capabilities + ceilings  
6. **Mode Select** — Assistant → Hybrid recommended as default  
7. **Test Lab** — Simulated conversations (availability, price, complaint, refund request)  
8. **Go Live** — Activate Binding + monitor first 48 hours (handover encouraged)  

## Rules

- Each step writes Agent Profile settings only.
- Channel must not go live before basic safety tests pass.
- Failed Test Lab blocks Go Live (or requires explicit override with Audit).
- Re-running the wizard updates profile; it does not recreate historical conversations.

## Outputs

- Channel Binding (active/inactive)
- Persona Profile
- Permission Policy snapshot
- Selected Operating Mode
- Initial Knowledge Base seeds from atelier facts
