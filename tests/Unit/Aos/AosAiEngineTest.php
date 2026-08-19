<?php

declare(strict_types=1);

namespace Tests\Unit\Aos;

use DressnMore\Aos\Ai\Application\AiEngine;
use DressnMore\Aos\Ai\Architecture\AiScopeDecision;
use DressnMore\Aos\Ai\Domain\Request\AiRequest;
use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use Tests\TestCase;

final class AosAiEngineTest extends TestCase
{
    public function test_ai_module_is_registered(): void
    {
        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        $this->assertTrue($registry->has('aos.ai'));
    }

    public function test_engine_completes_via_stub_provider(): void
    {
        /** @var AiEngine $engine */
        $engine = $this->app->make(AiEngine::class);
        $response = $engine->complete(AiRequest::create('اختبار الإكمال'));
        $this->assertNotSame('', $response->completion());
    }

    public function test_sprint10_scope_excludes_sdk_and_http(): void
    {
        $excluded = AiScopeDecision::excludedConcerns();
        $this->assertContains('openai_sdk', $excluded);
        $this->assertContains('http_client', $excluded);
        $this->assertContains('database', $excluded);
        $this->assertSame(['dressnmore/aos-ai'], AiScopeDecision::includedPackages());
    }
}
