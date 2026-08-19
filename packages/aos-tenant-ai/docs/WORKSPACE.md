# Workspace

Each Tenant has one AI Workspace (`AiWorkspaceManager::ensureForTenant`).

Loads/holds: language, timezone, currency, subscription plan, AI enabled flag, settings bag.

Events: `WorkspaceCreated`, `WorkspaceUpdated`.
