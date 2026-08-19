# Smart Assistant Product — Module Definition

## Module
- Key: `platform.smart-assistant`
- Title: Smart Assistant / المساعد الذكي
- Version: 0.23.0
- Package: `dressnmore/smart-assistant-product`

## Plan features
- `smart_assistant.enabled` — module on plan
- `smart_assistant.auto_reply` — planner auto-reply (enterprise default); template replies work when channel connected

## Permissions
- `smart_assistant.access`
- `smart_assistant.channels`
- `smart_assistant.messages`
- `smart_assistant.comments`
- `smart_assistant.automations`
- `smart_assistant.settings`

## Channels
| Channel | Mode | Messages | Comments | Auto-reply |
|---------|------|----------|----------|------------|
| WhatsApp | live Meta Cloud API | yes | no | template / planner |
| Facebook | live Messenger Graph | yes | yes | template |
| Instagram | live Messaging Graph | yes | yes | template |

## Webhooks
- `GET/POST /api/webhooks/smart-assistant/whatsapp`
- `GET/POST /api/webhooks/smart-assistant/facebook`
- `GET/POST /api/webhooks/smart-assistant/instagram`
