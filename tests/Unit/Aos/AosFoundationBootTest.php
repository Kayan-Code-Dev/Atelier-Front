<?php

declare(strict_types=1);

namespace Tests\Unit\Aos;

use DressnMore\Aos\Core\Architecture\FoundationScopeDecision;
use DressnMore\Aos\Core\Configuration\Contracts\ConfigurationProviderInterface;
use DressnMore\Aos\Core\Kernel\Contracts\KernelInterface;
use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Observability\Contracts\HealthReporterInterface;
use DressnMore\Aos\Observability\Contracts\LoggerInterface;
use Tests\TestCase;

final class AosFoundationBootTest extends TestCase
{
    public function test_kernel_boots_to_ready_state(): void
    {
        $kernel = $this->app->make(KernelInterface::class);

        $this->assertTrue($kernel->isReady());
        $this->assertSame('ready', $kernel->state());
        $this->assertNotSame('', $kernel->version());
    }

    public function test_foundation_modules_are_registered(): void
    {
        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);

        $this->assertTrue($registry->has('aos.core'));
        $this->assertTrue($registry->has('aos.events'));
        $this->assertTrue($registry->has('aos.observability'));
        $this->assertTrue($registry->has('aos.conversation'));
        $this->assertTrue($registry->has('aos.tools'));
        $this->assertTrue($registry->has('aos.permissions'));
        $this->assertTrue($registry->has('aos.planner'));
        $this->assertTrue($registry->has('aos.prompts'));
        $this->assertTrue($registry->has('aos.memory'));
        $this->assertTrue($registry->has('aos.knowledge'));
        $this->assertTrue($registry->has('aos.ai'));
        $this->assertTrue($registry->has('aos.communication'));
        $this->assertTrue($registry->has('aos.workflow'));
    }

    public function test_configuration_provider_exposes_foundation_flags(): void
    {
        /** @var ConfigurationProviderInterface $config */
        $config = $this->app->make(ConfigurationProviderInterface::class);

        $this->assertTrue($config->isFeatureEnabled('business_tools'));
        $this->assertTrue($config->isFeatureEnabled('ai_providers'));
        $this->assertFalse($config->isFeatureEnabled('channels_whatsapp'));
        $this->assertTrue($config->isFeatureEnabled('communication_hub'));
        $this->assertTrue($config->isFeatureEnabled('workflow_automation'));
        $this->assertTrue($config->isFeatureEnabled('planner'));
        $this->assertTrue($config->isFeatureEnabled('prompts'));
        $this->assertTrue($config->isFeatureEnabled('memory'));
        $this->assertTrue($config->isFeatureEnabled('knowledge'));
        $this->assertTrue($config->isFeatureEnabled('conversations'));
    }

    public function test_event_bus_and_observability_contracts_are_bound(): void
    {
        $this->assertInstanceOf(EventBusInterface::class, $this->app->make(EventBusInterface::class));
        $this->assertInstanceOf(LoggerInterface::class, $this->app->make(LoggerInterface::class));
        $this->assertInstanceOf(HealthReporterInterface::class, $this->app->make(HealthReporterInterface::class));
    }

    public function test_foundation_scope_decision_excludes_product_concerns(): void
    {
        $excluded = FoundationScopeDecision::excludedConcerns();

        $this->assertContains('business_logic', $excluded);
        $this->assertContains('openai', $excluded);
        $this->assertContains('whatsapp', $excluded);
        $this->assertSame([
            'dressnmore/aos-core',
            'dressnmore/aos-events',
            'dressnmore/aos-observability',
        ], FoundationScopeDecision::includedPackages());
    }

    public function test_health_reporter_returns_structure(): void
    {
        /** @var HealthReporterInterface $reporter */
        $reporter = $this->app->make(HealthReporterInterface::class);
        $report = $reporter->report();

        $this->assertArrayHasKey('healthy', $report);
        $this->assertArrayHasKey('checks', $report);
        $this->assertTrue($report['healthy']);
    }
}
