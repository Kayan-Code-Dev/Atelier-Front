<?php

declare(strict_types=1);

namespace DressnMore\Aos\Knowledge\Domain\Pipeline;

enum KnowledgeRetrievalStage: string
{
    case PlanningRequest = 'planning_request';
    case KnowledgeRequest = 'knowledge_request';
    case KnowledgeSearch = 'knowledge_search';
    case CandidateRanking = 'candidate_ranking';
    case PolicyFiltering = 'policy_filtering';
    case TenantIsolation = 'tenant_isolation';
    case KnowledgeCompression = 'knowledge_compression';
    case KnowledgeContextReady = 'knowledge_context_ready';
}
