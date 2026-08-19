<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Infrastructure\Bootstrap;

use DressnMore\Aos\Ai\Domain\Capability\ModelCapability;
use DressnMore\Aos\Ai\Domain\Factory\ProviderFactory;
use DressnMore\Aos\Ai\Domain\Model\ModelDescriptor;
use DressnMore\Aos\Ai\Domain\Model\ModelId;
use DressnMore\Aos\Ai\Domain\Provider\ProviderDescriptor;
use DressnMore\Aos\Ai\Domain\Provider\ProviderId;
use DressnMore\Aos\Ai\Domain\Provider\ProviderKind;
use DressnMore\Aos\Ai\Domain\Provider\ProviderManager;
use DressnMore\Aos\Ai\Infrastructure\InMemory\StubAiProvider;

final class BuiltinAiCatalogBootstrap
{
    public function __construct(
        private readonly ProviderManager $manager,
        private readonly ProviderFactory $factory = new ProviderFactory(),
    ) {}

    public function seed(): void
    {
        $chat = [
            ModelCapability::ChatCompletion,
            ModelCapability::Streaming,
            ModelCapability::JsonMode,
        ];
        $rich = [
            ...$chat,
            ModelCapability::StructuredOutput,
            ModelCapability::FunctionCalling,
            ModelCapability::Reasoning,
        ];

        $this->register(ProviderKind::OpenAi, 'openai', 'OpenAI', '1.0.0', $rich, [
            ['openai-gpt-4o', 'gpt-4o', '4o', 0.005, 0.015, 128000, 700],
            ['openai-gpt-4o-mini', 'gpt-4o-mini', '4o-mini', 0.00015, 0.0006, 128000, 500],
        ], 10);

        $this->register(ProviderKind::AzureOpenAi, 'azure_openai', 'Azure OpenAI', '1.0.0', $rich, [
            ['azure-gpt-4o', 'azure-gpt-4o', '4o', 0.005, 0.015, 128000, 750],
        ], 20);

        $this->register(ProviderKind::AnthropicClaude, 'anthropic', 'Anthropic Claude', '1.0.0', $rich, [
            ['claude-sonnet', 'claude-3-5-sonnet', '3.5', 0.003, 0.015, 200000, 650],
        ], 15);

        $this->register(ProviderKind::GoogleGemini, 'gemini', 'Google Gemini', '1.0.0', [
            ...$chat,
            ModelCapability::Vision,
        ], [
            ['gemini-flash', 'gemini-1.5-flash', '1.5', 0.0001, 0.0004, 1000000, 600],
        ], 25);

        $this->register(ProviderKind::Ollama, 'ollama', 'Ollama', '1.0.0', $chat, [
            ['ollama-llama3', 'llama3', '3', 0.0, 0.0, 8192, 400],
        ], 5);

        $this->register(ProviderKind::LlamaCpp, 'llamacpp', 'llama.cpp', '1.0.0', $chat, [
            ['llamacpp-local', 'local-gguf', '1', 0.0, 0.0, 4096, 350],
        ], 8);

        $this->register(ProviderKind::Vllm, 'vllm', 'vLLM', '1.0.0', $chat, [
            ['vllm-default', 'vllm-model', '1', 0.0, 0.0, 16384, 450],
        ], 12);

        $this->register(ProviderKind::OpenRouter, 'openrouter', 'OpenRouter', '1.0.0', $rich, [
            ['openrouter-auto', 'openrouter/auto', '1', 0.002, 0.006, 128000, 900],
        ], 30);

        $this->register(ProviderKind::Future, 'future', 'Future Providers', '0.0.0', [ModelCapability::ChatCompletion], [
            ['future-model', 'future-model', '0', 0.001, 0.002, 8192, 1000],
        ], 100, enabled: false);
    }

    /**
     * @param  list<ModelCapability>  $capabilities
     * @param  list<array{0: string, 1: string, 2: string, 3: float, 4: float, 5: int, 6: int}>  $models
     */
    private function register(
        ProviderKind $kind,
        string $id,
        string $name,
        string $version,
        array $capabilities,
        array $models,
        int $priority,
        bool $enabled = true,
        bool $failing = false,
    ): void {
        $providerId = ProviderId::fromString($id);
        $modelIds = array_map(static fn (array $m): string => $m[0], $models);
        $descriptor = new ProviderDescriptor(
            $providerId,
            $kind,
            $name,
            $version,
            $capabilities,
            $modelIds,
            $priority,
            $enabled,
        );

        $plugin = new StubAiProvider($id, $kind, $failing);
        $this->manager->register($descriptor, $plugin);

        foreach ($models as [$mid, $mname, $mver, $in, $out, $ctx, $latency]) {
            $this->manager->registerModel(new ModelDescriptor(
                ModelId::fromString($mid),
                $providerId,
                $mname,
                $mver,
                $capabilities,
                $in,
                $out,
                $ctx,
                $latency,
                $enabled,
            ));
        }
    }
}
