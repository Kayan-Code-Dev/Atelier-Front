# Security

- Multi-tenant ids on all tenant-owned domain models  
- Channel credentials stored as opaque refs only  
- No secrets in events  
- No execution surface in this package (reduces attack surface)  
- Architecture freeze prevents ad-hoc redesign that would break isolation contracts
