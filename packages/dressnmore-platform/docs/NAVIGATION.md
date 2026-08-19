# Navigation

## Tenant sidebar

FE should read `navigation.ai_assistant` from `/api/tenant/me` (or login).

Show the AI Assistant menu when `visible === true`.

| Item | Path | Permission |
|------|------|------------|
| Chat | `/tenant/ai` | `ai.chat` (or `ai.access`) |
| History | `/tenant/ai/history` | `ai.history` |
| Settings | `/tenant/ai/settings` | `ai.settings` |
| Memory | `/tenant/ai/memory` | `ai.memory` |
| Integrations | `/tenant/ai/integrations` | `ai.integrations` |
| Usage | `/tenant/ai/usage` | `ai.usage` |

Hidden when: module off, plan lacks `ai_assistant.enabled`, tenant denylisted, or user has no AI permissions.

## Admin

AI Assistant appears in plan feature catalog (`group: intelligence`) alongside Customers, Reservations/Inventory, Accounting, Reports.
