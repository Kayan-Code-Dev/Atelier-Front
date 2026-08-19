<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Tests\Unit;

use DressnMore\Aos\Ai\Application\AiEngine;
use DressnMore\Aos\Ai\Domain\Capability\ModelCapability;
use DressnMore\Aos\Ai\Domain\Health\HealthStatus;
use DressnMore\Aos\Ai\Domain\Provider\ProviderId;
use DressnMore\Aos\Ai\Domain\Request\AiRequest;
use DressnMore\Aos\Ai\Infrastructure\InMemory\StubAiProvider;
use DressnMore\Aos\Events\Contracts\EventBusInterface;
use PHPUnit\Framework\TestCase;

final class AiEngineTest extends TestCase
{
    private AiEngine $engine;

    protected function setUp(): void
    {
        $bus = new class implements EventBusInterface {
            public function publish(object $event): void {}

            public function subscribe(string $event, string|callable $listener): void {}
        };
        $this->engine = AiEngine::createDefault($bus);
    }

    public function test_provider_and_model_resolution(): void
    {
        $response = $this->engine->complete(AiRequest::create(
            'hello',
            preferredProviderId: ProviderId::fromString('anthropic'),
        ));

        $this->assertSame('anthropic', $response->providerId()->toString());
        $this->assertNotSame('', $response->completion());
    }

    public function test_fallback_when_preferred_unavailable(): void
    {
        $plugin = $this->engine->manager()->registry()->plugin(ProviderId::fromString('openai'));
        $this->assertInstanceOf(StubAiProvider::class, $plugin);
        $plugin->setAvailable(false);
        $this->engine->manager()->health()->mark(ProviderId::fromString('openai'), HealthStatus::Unhealthy);

        $response = $this->engine->complete(AiRequest::create(
            'need fallback',
            preferredProviderId: ProviderId::fromString('openai'),
        ));

        $this->assertNotSame('', $response->completion());
    }

    public function test_budget_and_latency_policies(): void
    {
        $response = $this->engine->complete(AiRequest::create(
            'cheap local',
            maxBudgetUsd: 0.000001,
            maxLatencyMs: 2000,
        ));

        $this->assertContains($response->providerId()->toString(), [
            'ollama', 'llamacpp', 'vllm', 'openai', 'anthropic', 'gemini', 'azure_openai', 'openrouter',
        ]);
    }

    public function test_health_monitoring(): void
    {
        $status = $this->engine->manager()->health()->probe(ProviderId::fromString('ollama'));
        $this->assertTrue($status->isUsable());
    }

    public function test_streaming_pipeline(): void
    {
        $result = $this->engine->stream(AiRequest::create(
            'stream me',
            requiredCapabilities: [ModelCapability::ChatCompletion, ModelCapability::Streaming],
        ));

        $this->assertNotEmpty($result['chunks']);
        $this->assertNotSame('', $result['response']->completion());
    }
}
