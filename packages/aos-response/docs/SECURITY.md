# Security

- No stack traces or exception class names in user messages
- Payload filter blocks password/token/secret/stack/sql keys
- Tenant / plan / conversation ids are metadata only — not leaked as errors
- Does not bypass permission/subscription gates (those remain on Planner/Gateway)
- Simulator mode is for demos/tests; production should inject real `ToolGateway`
