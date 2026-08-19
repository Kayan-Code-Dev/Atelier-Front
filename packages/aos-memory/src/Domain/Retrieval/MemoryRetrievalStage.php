<?php

declare(strict_types=1);

namespace DressnMore\Aos\Memory\Domain\Retrieval;

enum MemoryRetrievalStage: string
{
    case PlanningRequest = 'planning_request';
    case RetrieveWorkingMemory = 'retrieve_working_memory';
    case RetrieveConversationMemory = 'retrieve_conversation_memory';
    case RetrieveCustomerMemory = 'retrieve_customer_memory';
    case RetrieveBusinessMemory = 'retrieve_business_memory';
    case RankMemories = 'rank_memories';
    case CompressContext = 'compress_context';
    case MemoryContextReady = 'memory_context_ready';
}
