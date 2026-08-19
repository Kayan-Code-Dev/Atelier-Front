<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Pipeline;

enum MemoryWriteStage: string
{
    case ConversationUpdated = 'conversation_updated';
    case ExtractCandidateFacts = 'extract_candidate_facts';
    case MemoryClassification = 'memory_classification';
    case PolicyEvaluation = 'policy_evaluation';
    case ImportanceScoring = 'importance_scoring';
    case DuplicateDetection = 'duplicate_detection';
    case Summarization = 'summarization';
    case MemoryConsolidation = 'memory_consolidation';
    case MemoryStorage = 'memory_storage';
    case IndexUpdate = 'index_update';
    case MemoryReady = 'memory_ready';
}
