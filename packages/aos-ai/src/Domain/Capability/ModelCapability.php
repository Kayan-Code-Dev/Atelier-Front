<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Capability;

enum ModelCapability: string
{
    case ChatCompletion = 'chat_completion';
    case StructuredOutput = 'structured_output';
    case FunctionCalling = 'function_calling';
    case Streaming = 'streaming';
    case Vision = 'vision';
    case Audio = 'audio';
    case Reasoning = 'reasoning';
    case JsonMode = 'json_mode';
    case Embeddings = 'embeddings'; // Future
}
