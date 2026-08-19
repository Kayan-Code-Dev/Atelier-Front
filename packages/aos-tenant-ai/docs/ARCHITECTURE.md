# Architecture

Tenant-scoped AI control plane above AOS execution packages.

| Layer | Contents |
|-------|----------|
| Contracts | Provider ports only |
| Domain | Workspace, Conversation, Message, Session, Context, Permission, Subscription, Integration, Memory, Policies, Events |
| Application | Managers / Resolvers / Guards |
| Infrastructure | In-memory providers (tests/demo) |

Does **not** implement Planner, Gateway, tools, LLM calls, or live channel adapters.
