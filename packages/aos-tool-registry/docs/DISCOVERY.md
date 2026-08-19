# Discovery

`ToolDiscovery` exposes discoverable tools to the Planner:

- Status = Active
- Availability ≠ Unavailable
- Visibility ≠ Restricted

## Operations

| Method | Use |
|--------|-----|
| `discover(category?, ownerDomain?)` | Catalog browse |
| `find(toolName)` | Single lookup |
| `byCapability(capability)` | Capability-driven search |

## Resolution

`ToolResolver::resolve(name, minimumVersion?)`:

- Missing / non-discoverable → reject (`ToolDiscoveryRejected`)
- Version incompatible → reject (`ToolVersionIncompatible`)
- Else → return `ToolDescriptor` for Gateway/Planner consumption
