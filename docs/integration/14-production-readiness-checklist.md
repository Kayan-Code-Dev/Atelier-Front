# 14 — Production Readiness Checklist

Checklist for going live as a Digital Employee on DressnMore.  
Mark each item: ☐ Not started · ◐ In progress · ✅ Done · ⛔ Blocked

## Architecture

- ☐ Composition-root orchestrator wires Master Sequence  
- ☐ No Domain → Eloquent/SDK imports in AOS packages  
- ☐ Dependency rules from doc 07 enforced in review  
- ☐ Extension points used for WhatsApp & Tool adapters  

## Security

- ☐ Webhook signature verification per channel  
- ☐ Secrets in secure store (not repo)  
- ☐ Tenant isolation tests for memory/knowledge/tools  
- ☐ Prompt Guard enabled on all provider calls  
- ☐ Critical tools Always-Approval verified  
- ☐ PII redaction in logs  

## Performance

- ☐ Ingress async after normalize under load  
- ☐ Provider latency budgets enforced  
- ☐ Context size budgets (memory/knowledge)  
- ☐ Channel send timeouts + retries  

## Caching

- ☐ Knowledge published cache policy defined  
- ☐ Settings/hours cache TTLs  
- ☐ No cross-tenant cache keys  

## Monitoring

- ☐ Provider failure/fallback alerts  
- ☐ Channel health alerts  
- ☐ Workflow failure/DLQ alerts  
- ☐ Approval queue SLA alerts  

## Logging

- ☐ Correlation id per message/turn  
- ☐ Structured logs with tenant_id (no secrets)  
- ☐ Retention policy defined  

## Observability

- ☐ Health endpoints include module status  
- ☐ Metrics: conversations, tool success, token cost, escalation rate  
- ☐ Trace spans across Hub → Planner → Provider → Tools  

## Testing

- ☐ Unit tests per package (existing)  
- ☐ Contract tests for Tool adapters  
- ☐ Golden conversation evals per persona  
- ☐ Isolation negative tests  
- ☐ HITL approval path tests  

## Disaster Recovery

- ☐ Provider multi-fallback verified  
- ☐ Channel reconnect runbooks  
- ☐ Workflow replay/DLQ runbooks  

## Backup

- ☐ Conversation/audit backup policy  
- ☐ Knowledge corpus backup  
- ☐ Memory store backup (when persistent)  

## AI Governance

- ☐ Persona/prompt versioning ownership  
- ☐ Full Auto enablement checklist per tenant  
- ☐ Model change approval process  
- ☐ Cost budgets per tenant  
- ☐ Human handover quality review cadence  

## Integration-specific (DressnMore)

- ☐ Channel Binding Registry populated  
- ☐ MVP Tool adapters deployed  
- ☐ Staging E2E WhatsApp → Reply proven  
- ☐ Production dark-launch tenant selected  
- ☐ Rollback plan for adapter release  

## Exit criterion

Production “Digital Employee live” requires **Architecture + Security + MVP Integration + HITL Approvals + Monitoring** all ✅.  
UI Workspace polish alone is **not** sufficient.
