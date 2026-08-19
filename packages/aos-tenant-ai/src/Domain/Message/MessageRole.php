<?php

declare(strict_types=1);

namespace DressnMore\Aos\TenantAi\Domain\Message;

enum MessageRole: string
{
    case User = 'user';
    case Assistant = 'assistant';
    case System = 'system';
    case ToolCall = 'tool_call';
    case ToolResult = 'tool_result';
}
