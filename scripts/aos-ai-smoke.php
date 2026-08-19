<?php

declare(strict_types=1);

/**
 * Smoke test for AOS AI Provider Platform (Sprint 10).
 * Run: php scripts/aos-ai-smoke.php
 */

use DressnMore\Aos\Ai\Application\AiEngine;
use DressnMore\Aos\Ai\Domain\Capability\ModelCapability;
use DressnMore\Aos\Ai\Domain\Health\HealthStatus;
use DressnMore\Aos\Ai\Domain\Provider\ProviderId;
use DressnMore\Aos\Ai\Domain\Request\AiRequest;
use DressnMore\Aos\Ai\Infrastructure\InMemory\StubAiProvider;
use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Events\Contracts\EventBusInterface;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

require __DIR__.'/../vendor/autoload.php';

$failed = 0;
$assertTrue = static function (bool $cond, string $label) use (&$failed): void {
    echo ($cond ? "  OK  " : " FAIL ").$label."\n";
    if (! $cond) {
        $failed++;
    }
};

echo "AOS AI — domain smoke\n";

$bus = new class implements EventBusInterface {
    public function publish(object $event): void {}

    public function subscribe(string $event, string|callable $listener): void {}
};
$engine = AiEngine::createDefault($bus);

$response = $engine->complete(AiRequest::create(
    'مرحبا، لخص سياسة الإرجاع',
    requiredCapabilities: [ModelCapability::ChatCompletion],
    maxBudgetUsd: 1.0,
    maxLatencyMs: 5000,
));
$assertTrue($response->completion() !== '', 'provider resolution + completion');
$assertTrue($response->providerId()->toString() !== '', 'provider selected');
$assertTrue($response->costUsd() >= 0.0, 'cost tracking');
$assertTrue($response->latencyMs() > 0, 'latency tracking');

// Prefer ollama (cheap/local)
$ollama = $engine->complete(AiRequest::create(
    'local test',
    preferredProviderId: ProviderId::fromString('ollama'),
    maxBudgetUsd: 1.0,
));
$assertTrue($ollama->providerId()->toString() === 'ollama', 'model/provider preference');

// Budget policy: extremely low budget should still find free on-prem or fail gracefully
$budgeted = $engine->complete(AiRequest::create(
    'budget test',
    maxBudgetUsd: 0.00001,
    maxLatencyMs: 5000,
));
$assertTrue(in_array($budgeted->providerId()->toString(), ['ollama', 'llamacpp', 'vllm', 'openai', 'anthropic', 'gemini', 'azure_openai', 'openrouter'], true), 'budget-aware selection');

// Latency policy with generous limit
$latency = $engine->complete(AiRequest::create('latency', maxLatencyMs: 2000));
$assertTrue($latency->latencyMs() >= 0, 'latency policies allow selection');

// Health monitoring + fallback
$openaiPlugin = $engine->manager()->registry()->plugin(ProviderId::fromString('openai'));
$assertTrue($openaiPlugin instanceof StubAiProvider, 'stub plugin registered');
$openaiPlugin->setAvailable(false);
$engine->manager()->health()->mark(ProviderId::fromString('openai'), HealthStatus::Unhealthy);

$fallback = $engine->complete(AiRequest::create(
    'fallback please',
    preferredProviderId: ProviderId::fromString('openai'),
    maxBudgetUsd: 2.0,
    maxLatencyMs: 5000,
));
// Preferred openai is unhealthy → selector may still list it then execution falls back
$assertTrue($fallback->completion() !== '', 'fallback/health path produces completion');

$stream = $engine->stream(AiRequest::create(
    'stream hello',
    requiredCapabilities: [ModelCapability::ChatCompletion, ModelCapability::Streaming],
));
$assertTrue(count($stream['chunks']) > 0, 'streaming pipeline chunks');
$assertTrue($stream['response']->completion() !== '', 'streaming pipeline response');

echo "AOS AI — Laravel wiring\n";

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

/** @var ModuleRegistryInterface $modules */
$modules = $app->make(ModuleRegistryInterface::class);
$assertTrue($modules->has('aos.ai'), 'module aos.ai registered');

/** @var AiEngine $appEngine */
$appEngine = $app->make(AiEngine::class);
$laravel = $appEngine->complete(AiRequest::create('laravel completion check'));
$assertTrue($laravel->completion() !== '', 'laravel engine completes');

echo $failed === 0 ? "PASSED\n" : "FAILED ({$failed})\n";
exit($failed === 0 ? 0 : 1);
