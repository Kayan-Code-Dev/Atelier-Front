# AOS Omni-Channel Communication Hub (`dressnmore/aos-communication`)

**Sprint 11** — Unified communication layer for all customer channels.

## Purpose

Provide a contracts-first communication core that receives, normalizes, routes, and sends messages across channels without coupling AOS to SDKs, HTTP clients, or specific providers.

## Architecture

Hexagonal + DDD:

- **Application**: `CommunicationHub`
- **Domain**: channels, normalized messages, attachments, delivery, routing, policies, comment flow
- **Infrastructure**: in-memory stub channel adapters and bootstrap catalog
- **Contracts**: `CommunicationHubInterface`, `ChannelAdapterInterface`

## Channel Adapters

Every channel is an independent adapter plugin implementing `ChannelAdapterInterface`.

Conceptual channel types:

WhatsApp · Facebook Messenger · Instagram Direct · Facebook Comments · Instagram Comments · Telegram · Web Chat · Mobile App Chat · Email · Future Channels

## Message Pipeline

```
Receive
 → Normalize
 → Validate
 → Policy Check
 → Conversation Route
 → AI Processing (boundary placeholder)
 → Reply Generation (boundary placeholder)
 → Send
 → Track Delivery
```

All inbound payloads are transformed to `NormalizedMessage` before entering core flow.

## Comment Flow

```
Facebook/Instagram Comment
 → Comment Classifier
 → Need Reply?
 → Reply Publicly
 → Need Private Conversation?
 → Create Conversation
 → Open Messenger
```

## Delivery Tracking

Supported statuses:

Queued · Sent · Delivered · Read · Failed · Expired

Includes managers for delivery state, read receipts, and typing indicators.

## Extension Points

1. Add a new channel by implementing `ChannelAdapterInterface`
2. Register tenant-specific accounts in `ChannelRegistry`
3. Replace policy constraints in `ChannelPolicies`
4. Extend conversation routing strategy in `ConversationRouter`
5. Subscribe to communication domain events

## Architecture Decisions

- Contracts-first adapters; no business logic in adapters
- No SDK / No HTTP / No DB / No Laravel models
- Multi-tenant channel binding supported via registry keying
- Channel health and account metadata represented in domain model
- Event-driven design with communication lifecycle events

## Module

- Provider: `AosCommunicationServiceProvider`
- Module: `aos.communication`
- Feature flag: `communication_hub`
- Smoke: `php scripts/aos-communication-smoke.php`
