# AOS Knowledge Engine (`dressnmore/aos-knowledge`)

**Sprint 9** — Official enterprise knowledge platform for AOS.

## Purpose

Provide a **provider-agnostic knowledge plane**: register, version, publish, search, rank, and retrieve knowledge for Prompt / Planner consumers — without embeddings, vector DBs, or LLMs in this sprint.

| Does | Does not |
|------|----------|
| Knowledge lifecycle & versioning | Call OpenAI / Claude / Gemini |
| Global + tenant knowledge | Use embeddings / vector DB |
| Lexical search (swappable) | Execute Business Tools / Planner |
| Ranking + policy filtering | Talk to WhatsApp / Messenger |
| Tenant isolation | Touch Eloquent / Controllers / APIs |

## Knowledge Lifecycle

```
Draft → Review → Approved → Published → Archived / Deprecated
```

Only **Published** knowledge is retrievable.

## Knowledge Sources

Manual Entry · Uploaded Documents · PDF · Word · Markdown · HTML · Website · Future API · Future Database  

Sources are registry entries — ingest adapters plug in later.

## Knowledge Collections

Global · Tenant · Department · Private · Shared

## Retrieval Pipeline

```
Planning Request
 → Knowledge Request
 → Knowledge Search
 → Candidate Ranking
 → Policy Filtering
 → Tenant Isolation
 → Knowledge Compression (placeholder)
 → Knowledge Context Ready
```

## Ranking

Weighted: **Relevance · Freshness · Confidence · Importance · Popularity · Business Priority**

## Policies

Visibility · Access · Publishing · Versioning · Retention · Tenant Isolation · Compliance filtering

## Extension Points

1. Replace `KnowledgeSearchEngineInterface` with vector/hybrid search later  
2. Add source ingest adapters (PDF/API/DB) without Domain changes  
3. Tune `KnowledgePolicyEngine` / `KnowledgeRanker`  
4. Subscribe to knowledge domain events  
5. Fill compression + summaries placeholders  

## Architecture Decisions

- **Contracts first** — `KnowledgeEngineInterface` + repository/search ports  
- **No embeddings / vector DB / LLM** in Sprint 9  
- **Swappable search** — Domain depends on `KnowledgeSearchEngineInterface` only  
- **Global + Tenant** knowledge coexist with hard isolation  
- **Hexagonal / Event-driven** — in-memory adapters only  

## Module

- Provider: `AosKnowledgeServiceProvider`
- Module: `aos.knowledge`
- Feature flag: `knowledge`
- Smoke: `php scripts/aos-knowledge-smoke.php`
