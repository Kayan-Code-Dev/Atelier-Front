# Extensibility Guidelines — New Business Tools

## Adding a Tool

1. Confirm need from Use Cases / Intent Mapping (or new UC ADR).  
2. Assign Taxonomy group + Risk class.  
3. Write full 25-field Contract.  
4. Declare Capabilities; update Permission catalog vocabulary.  
5. Declare Dependencies + related Tools.  
6. Define Idempotency for any write.  
7. Add Audit + Analytics event names.  
8. Update Capability Matrix + Dependency Graph.  
9. Map intents in Use Cases Intent Mapping.  
10. Pass security review if Medium+.  

## Versioning

| Change | Version rule |
|--------|--------------|
| Additive optional output fields | Minor (v1.1) — old planners ignore |
| New required input | Major (v2) — new Tool name or version pin |
| Side-effect change | Major |
| Risk upgrade | Major + migration note |
| Deprecation | Mark Deprecated; keep read path N releases |

## Deprecating a Tool

1. Mark Deprecated in catalog with replacement Tool.  
2. Stop Discovery for new plans; allow in-flight.  
3. Remove from Intent Mapping after migration.  
4. Retain Audit history forever.  

## Forbidden extensions

- Tool that bypasses Permission Engine  
- Tool that accepts raw SQL / arbitrary service name  
- Channel-specific business Tool (channel limits belong in Reply path)  
- Silent Critical financial Tool without Approval  
