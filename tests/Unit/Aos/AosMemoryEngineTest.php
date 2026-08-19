<?php

declare(strict_types=1);

namespace Tests\Unit\Aos;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Memory\Application\MemoryEngine;
use DressnMore\Aos\Memory\Architecture\MemoryScopeDecision;
use DressnMore\Aos\Memory\Domain\Memory\ConversationMemoryUpdate;
use Tests\TestCase;

final class AosMemoryEngineTest extends TestCase
{
    public function test_memory_module_is_registered(): void
    {
        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        $this->assertTrue($registry->has('aos.memory'));
    }

    public function test_engine_remembers_and_recalls(): void
    {
        /** @var MemoryEngine $engine */
        $engine = $this->app->make(MemoryEngine::class);
        $result = $engine->remember(ConversationMemoryUpdate::create(
            'tenant_test',
            'conv_test',
            'cust_test',
            null,
            'أريد حجز بروفة',
        ));

        $this->assertGreaterThan(0, $result->count());
    }

    public function test_sprint8_scope_excludes_providers_and_raw_persistence(): void
    {
        $excluded = MemoryScopeDecision::excludedConcerns();
        $this->assertContains('openai', $excluded);
        $this->assertContains('business_tools', $excluded);
        $this->assertContains('database', $excluded);
        $this->assertContains('raw_message_persistence', $excluded);
        $this->assertSame(['dressnmore/aos-memory'], MemoryScopeDecision::includedPackages());
    }
}
