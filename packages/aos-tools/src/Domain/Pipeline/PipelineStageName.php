<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Pipeline;

enum PipelineStageName: string
{
    case Requested = 'tool_requested';
    case Discovered = 'tool_discovered';
    case Resolved = 'tool_resolved';
    case MetadataLoaded = 'tool_metadata_loaded';
    case InputValidated = 'input_validated';
    case ExecutionContextCreated = 'execution_context_created';
    case Authorization = 'authorization_hook';
    case Execute = 'execute_tool';
    case Normalize = 'normalize_result';
    case Audit = 'audit_hook';
    case Analytics = 'analytics_hook';
    case Completed = 'return_result';
}
