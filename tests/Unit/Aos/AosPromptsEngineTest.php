<?php

declare(strict_types=1);

namespace Tests\Unit\Aos;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Prompts\Application\PromptEngine;
use DressnMore\Aos\Prompts\Architecture\PromptsScopeDecision;
use DressnMore\Aos\Prompts\Domain\Persona\PersonaType;
use DressnMore\Aos\Prompts\Domain\Prompt\PromptBuildRequest;
use DressnMore\Aos\Prompts\Domain\Section\PromptSectionType;
use DressnMore\Aos\Prompts\Domain\Template\PromptTemplateType;
use Tests\TestCase;

final class AosPromptsEngineTest extends TestCase
{
    public function test_prompts_module_is_registered(): void
    {
        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        $this->assertTrue($registry->has('aos.prompts'));
    }

    public function test_engine_builds_provider_agnostic_prompt(): void
    {
        /** @var PromptEngine $engine */
        $engine = $this->app->make(PromptEngine::class);
        $doc = $engine->build(PromptBuildRequest::create(
            'أحتاج مساعدة في الشكوى',
            PersonaType::SupportAgent,
            PromptTemplateType::Complaint,
            tenantId: 'tenant_x',
        ));

        $this->assertNotNull($doc->section(PromptSectionType::System));
        $this->assertNotSame('', $doc->renderedText());
        $this->assertSame('aos.prompts', $doc->version()->generatedBy());
    }

    public function test_sprint7_scope_excludes_providers_and_channels(): void
    {
        $excluded = PromptsScopeDecision::excludedConcerns();
        $this->assertContains('openai', $excluded);
        $this->assertContains('claude', $excluded);
        $this->assertContains('gemini', $excluded);
        $this->assertContains('whatsapp', $excluded);
        $this->assertContains('database', $excluded);
        $this->assertSame(['dressnmore/aos-prompts'], PromptsScopeDecision::includedPackages());
    }
}
