# Security

- Tenant id is required on every planning context and stamped on the plan
- Permission Validator enforces user tool grants / permission lists
- Subscription Validator enforces plan-tier tool allowlists
- Policy Evaluator enforces org/system denylists, empty plans, conflicting tools, approvals
- No tool payloads are executed; plans carry **identifiers only**
- No LLM calls — reduces prompt-injection surface in this package
- In-memory repository is for tests/demo; production adapters must isolate by tenant
