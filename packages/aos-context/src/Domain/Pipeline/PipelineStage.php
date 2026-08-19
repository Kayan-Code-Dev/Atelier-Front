<?php

declare(strict_types=1);

namespace DressnMore\Aos\Context\Domain\Pipeline;

enum PipelineStage: string
{
    case IncomingMessage = 'incoming_message';
    case ChannelResolved = 'channel_resolved';
    case TenantResolved = 'tenant_resolved';
    case CustomerResolved = 'customer_resolved';
    case ContactResolved = 'contact_resolved';
    case ConversationResolved = 'conversation_resolved';
    case BranchResolved = 'branch_resolved';
    case LanguageResolved = 'language_resolved';
    case TimezoneResolved = 'timezone_resolved';
    case BusinessContextProvided = 'business_context_provided';
    case CustomerContextProvided = 'customer_context_provided';
    case ConversationContextProvided = 'conversation_context_provided';
    case WorkingHoursProvided = 'working_hours_provided';
    case PermissionContextProvided = 'permission_context_provided';
    case SnapshotBuilt = 'snapshot_built';
    case ContextReady = 'context_ready';
    case Failed = 'failed';
}
