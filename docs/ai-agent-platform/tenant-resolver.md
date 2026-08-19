# Tenant Resolver & Isolation Guard

## Purpose

Map every inbound channel message to exactly one DressnMore tenant and prevent cross-tenant data access for the digital employee pipeline.

## Binding model (conceptual)

Each channel account is bound explicitly:

| Channel | Binding key examples |
|---------|----------------------|
| WhatsApp | `phone_number_id` / WABA account → `tenant_id` |
| Messenger / Instagram | `page_id` → `tenant_id` |
| Web Chat | Widget / inbox public key → `tenant_id` |
| Email | Inbound address / domain route → `tenant_id` |

**Forbidden:** resolving tenant from message text, atelier name in content, or “first matching” heuristics.

## Resolution algorithm

1. Adapter extracts `channel_type + channel_account_id`.
2. Resolver reads **Channel Binding Registry** (platform-level Agent registry).
3. Validates: binding active + subscription allows AI Agent + channel enabled.
4. Emits `TenantExecutionContext { tenant_id, agent_id, channel_binding_id, isolation_key }`.
5. Every Tool / Memory / KB call must carry the same `isolation_key`; mismatch = hard fail.

## Anti cross-tenant controls

- Registry lookups by unique channel account key only
- No fallback to “default tenant” or shared inbox
- Conversations and memory logically partitioned per tenant
- Audit on any attempt to use a `tenant_id` different from the binding
- Staff see only their tenant conversations via existing tenant session
- Platform Super Admin support access is break-glass + audited — not the normal Agent path

## Relationship to existing DressnMore resolver

| Path | Mechanism |
|------|-----------|
| App / API (humans) | Existing `X-Tenant` / query / subdomain → `tenant_{slug}` |
| Channels (customers) | Channel Binding Registry → tenant, then open tenant scope for Tools |

Channel clients do not send `X-Tenant`. After binding resolution, Tools execute against the same tenant database isolation model used today (`tenant_{slug}`).

## Outputs consumed downstream

- Conversation Manager scopes thread identity
- Context Engine scopes customer and facts
- Permission Engine loads that tenant’s Agent policy
- Audit stamps `tenant_id` + `channel_binding_id` on every decision
