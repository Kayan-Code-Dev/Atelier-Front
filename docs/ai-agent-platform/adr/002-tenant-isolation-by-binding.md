# ADR-002: Tenant Isolation by Channel Binding

- **Status:** Accepted
- **Date:** 2026-08-06
- **Context:** Human API traffic already resolves tenants via `X-Tenant` / subdomain. Channel customers cannot send those headers.
- **Decision:** Resolve tenant solely via an explicit **Channel Binding Registry** (`channel_account_id → tenant_id`). Never infer tenant from message content. After resolution, Tools run under the same `tenant_{slug}` isolation model.
- **Consequences:** Unbound messages are rejected and audited. Shared inboxes across tenants are forbidden. Support break-glass is separate and audited.
