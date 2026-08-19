# Registration Flow

```
ProviderDescriptor
   ↓
CapabilityDescriptor(s)
   ↓
ToolDescriptor(s)  — validated against Capability Registry
   ↓
PolicyDescriptor / ApprovalDescriptor (optional references)
   ↓
IntentDescriptor (tool plan + required capabilities)
```

## ToolRegistrar API (conceptual)

1. `registerProvider`
2. `registerCapability`
3. `registerTool` (runs ToolValidator)
4. `registerPolicy` / `registerApproval`
5. `registerIntent`

## Events

- `ToolRegisteredInPlatform`
- `CapabilityRegistered`
- `IntentRegistered`

## Order invariant

Capabilities **must** exist before tools that declare them. Intents may be registered after tools.
