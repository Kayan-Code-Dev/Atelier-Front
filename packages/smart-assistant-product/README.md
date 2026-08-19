# Smart Assistant Product (`dressnmore/smart-assistant-product`)

**المساعد الذكي** — social automation product module (v0.23).

Built on frozen architecture `dressnmore/smart-assistant` v1.0.0 — does not modify core contracts.

## Distinct from المستشار الذكي
| | المستشار الذكي (`ai_assistant`) | المساعد الذكي (`smart_assistant`) |
|--|----------------------------------|-------------------------------------|
| Purpose | Business Q&A on atelier data | Social channel automation |
| Module | `platform.ai-integration` | `platform.smart-assistant` |
| Plan key | `ai_assistant.enabled` | `smart_assistant.enabled` |

## WhatsApp (live — Meta Cloud API)
- Persist encrypted credentials (`phone_number_id`, `access_token`) in central DB
- Public webhook verify + inbound receive
- Signature check via `META_WHATSAPP_APP_SECRET` (optional until `META_WHATSAPP_REQUIRE_SIGNATURE=true`)
- Outbound text send via Graph API
- Auto-reply job (`template` by default, `planner` when plan allows)

Facebook / Instagram remain stub connectors.

## Env
```
SMART_ASSISTANT_WEBHOOK_VERIFY_TOKEN=your-verify-token
META_WHATSAPP_APP_SECRET=
META_WHATSAPP_REQUIRE_SIGNATURE=false
META_WHATSAPP_GRAPH_VERSION=v21.0
META_WHATSAPP_API_BASE=https://graph.facebook.com
SMART_ASSISTANT_WA_AUTO_REPLY_MODE=template
QUEUE_CONNECTION=database
```

Webhook URL: `https://{api-host}/api/webhooks/smart-assistant/whatsapp`

## API (tenant auth)
Prefix: `/api/tenant/smart-assistant`
- `GET /` dashboard
- `GET /navigation`
- `GET|PUT|PATCH /settings` (auto-reply toggle)
- `GET /channels` · `POST /channels/{type}/connect` · `POST /channels/{type}/disconnect`
- `GET /messages` · `POST /messages/reply`
- `GET /comments` · `POST /comments/reply`

WhatsApp connect body: `phone_number_id`, `access_token`, optional `waba_id`, `auto_reply_enabled`, `auto_reply_mode`.

## Migration
`database/migrations/2026_08_07_210000_create_smart_assistant_channel_tables.php` (central connection).
