# AOS Memory Engine (`dressnmore/aos-memory`)

**Sprint 8** — Official memory system for AOS digital employees.

## Purpose

Retain **classified, important facts** across conversations; retrieve them efficiently; prevent context explosion — without depending on any AI provider or database.

| Does | Does not |
|------|----------|
| Short / long-term & working memory | Call OpenAI / Claude / Gemini |
| Customer / business / preference / episodic memory | Execute Business Tools |
| Summaries & snapshots | Talk to WhatsApp / Messenger |
| Ranking, consolidation, expiration | Persist raw chat transcripts as durable memory |
| Tenant + customer isolation | Touch Eloquent / Controllers / APIs |

## Memory Types

| Type | Role |
|------|------|
| Working | Session-scoped intent signals |
| Conversation | Conversation-scoped facts |
| Short-Term / Long-Term | Promotion path by importance |
| Customer / Preference | Customer-scoped durable facts |
| Business / Operational | Tenant-scoped operational facts |
| Episodic | Notable conversation episodes |
| Summary | Classified summary artifacts |
| Semantic | Placeholder for future embeddings |

## Memory Lifecycle (Write Pipeline)

```
Conversation Updated
 → Extract Candidate Facts
 → Memory Classification
 → Policy Evaluation
 → Importance Scoring
 → Duplicate Detection
 → Summarization
 → Memory Consolidation
 → Memory Storage
 → Index Update
 → Memory Ready
```

**Constraint:** raw messages are never stored as durable memory — only classified facts / summaries.

## Retrieval Pipeline

```
Planning Request
 → Working Memory
 → Conversation Memory
 → Customer Memory
 → Business Memory
 → Rank Memories
 → Compress Context (placeholder)
 → Memory Context Ready
```

Output is a provider-agnostic `MemoryContext` (renderable text + structured records) for Prompt Engine.

## Ranking

Weighted score: **Recency · Importance · Relevance · Confidence · Frequency**.

## Retention Policies

Expiration: Session / ShortLived / Rolling / LongLived / Permanent  

Retention scope: Ephemeral / Conversation / Customer / Tenant / Durable  

Policies also cover replacement, deduplication, compression flags, privacy redaction, and hard tenant/customer isolation.

## Extension Points

1. Swap `MemoryStoreInterface` / `MemoryIndexInterface` for Redis/SQL later  
2. Replace `MemoryFactExtractor` with richer NLP (still no provider inside this package core)  
3. Tune `MemoryPolicy` thresholds  
4. Subscribe to memory domain events  
5. Fill Semantic memory + real compression in a later sprint  

## Architecture Decisions

- **Contracts first** — `MemoryEngineInterface` is the application port  
- **Hexagonal** — Domain never imports Laravel models or DB drivers  
- **Event-driven** — create / expire / retrieve / summarize / snapshot events  
- **Opaque inputs** — no hard coupling to Conversation / Planner types  
- **No AI provider** — rule-based extraction & summarization in Sprint 8  

## Module

- Provider: `AosMemoryServiceProvider`
- Module: `aos.memory`
- Feature flag: `memory`
- Smoke: `php scripts/aos-memory-smoke.php`
