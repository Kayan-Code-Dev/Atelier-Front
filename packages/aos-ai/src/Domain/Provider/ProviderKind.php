<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Provider;

enum ProviderKind: string
{
    case OpenAi = 'openai';
    case AzureOpenAi = 'azure_openai';
    case AnthropicClaude = 'anthropic_claude';
    case GoogleGemini = 'google_gemini';
    case Ollama = 'ollama';
    case LlamaCpp = 'llama_cpp';
    case Vllm = 'vllm';
    case OpenRouter = 'openrouter';
    case Future = 'future';
}
