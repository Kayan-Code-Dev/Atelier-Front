# Permissions

| Key | Purpose |
|-----|---------|
| `ai.access` | Enter AI Assistant / unlock nav |
| `ai.chat` | Chat screen |
| `ai.history` | History screen |
| `ai.memory` | Memory screen |
| `ai.integrations` | Integrations screen |
| `ai.settings` | Settings screen |
| `ai.usage` | Usage screen |

Legacy keys `intelligence.view` / `intelligence.chat` remain for `/intelligence/*` APIs.

Owner receives all keys via `TenantRolePermissionSeeder`. Manager receives `ai.access`, `ai.chat`, `ai.history`, `ai.usage`.
