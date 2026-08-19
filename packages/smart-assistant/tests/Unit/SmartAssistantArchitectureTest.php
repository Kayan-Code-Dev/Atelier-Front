<?php

declare(strict_types=1);

namespace DressnMore\SmartAssistant\Tests\Unit;

use DressnMore\SmartAssistant\Architecture\ArchitectureVersion;
use DressnMore\SmartAssistant\Architecture\BoundedContext;
use DressnMore\SmartAssistant\Architecture\ExtensionPoints;
use DressnMore\SmartAssistant\Capability\AgentCapabilityCatalog;
use DressnMore\SmartAssistant\Contracts\Channel\ChannelConnectorInterface;
use DressnMore\SmartAssistant\Domain\Agent\Agent;
use DressnMore\SmartAssistant\Domain\Agent\AgentTypeCatalog;
use DressnMore\SmartAssistant\Domain\Channel\Channel;
use DressnMore\SmartAssistant\Domain\Channel\ChannelTypeCatalog;
use DressnMore\SmartAssistant\Domain\Policy\PolicyCatalog;
use DressnMore\SmartAssistant\Domain\Policy\TenantIsolationPolicy;
use DressnMore\SmartAssistant\Registry\DescriptorAgent;
use DressnMore\SmartAssistant\Registry\DescriptorChannel;
use DressnMore\SmartAssistant\Registry\InMemoryAgentRegistry;
use DressnMore\SmartAssistant\Registry\InMemoryCapabilityRegistry;
use DressnMore\SmartAssistant\Registry\InMemoryChannelRegistry;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class SmartAssistantArchitectureTest extends TestCase
{
    public function test_architecture_is_frozen_v1(): void
    {
        $this->assertTrue(ArchitectureVersion::isFrozen());
        $this->assertSame('1.0.0', ArchitectureVersion::semver());
        $this->assertSame('smart.assistant', ArchitectureVersion::MODULE);
    }

    public function test_bounded_contexts_defined(): void
    {
        $this->assertCount(11, BoundedContext::all());
        $this->assertContains(BoundedContext::ASSISTANT_CORE, BoundedContext::all());
        $this->assertContains(BoundedContext::CHANNEL, BoundedContext::all());
    }

    public function test_register_agent_without_core_change(): void
    {
        $registry = new InMemoryAgentRegistry();
        $agent = new DescriptorAgent(new Agent('agent.custom.demo', AgentTypeCatalog::CUSTOM, 'Demo', ['custom.demo']));
        $registry->register($agent);

        $this->assertTrue($registry->has('agent.custom.demo'));
        $this->assertSame('Demo', $registry->get('agent.custom.demo')?->identity()->name());
    }

    public function test_register_channel_without_core_change(): void
    {
        $registry = new InMemoryChannelRegistry();
        $channel = new DescriptorChannel(new Channel('channel.custom.sms', 'sms', 'SMS'));
        $registry->register($channel);

        $this->assertTrue($registry->has('channel.custom.sms'));
        $this->assertSame('sms', $registry->get('channel.custom.sms')?->type());
    }

    public function test_capability_catalog_seeds_all_agent_types(): void
    {
        $registry = new InMemoryCapabilityRegistry();
        (new AgentCapabilityCatalog())->seed($registry);

        $this->assertNotEmpty($registry->forAgentType('sales'));
        $this->assertNotEmpty($registry->forAgentType('support'));
        $this->assertNotEmpty($registry->forAgentType('marketing'));
        $this->assertNotEmpty($registry->forAgentType('social'));
        $this->assertNotEmpty($registry->forAgentType('analytics'));
        $this->assertNotEmpty($registry->forAgentType('automation'));
        $this->assertGreaterThanOrEqual(20, count($registry->all()));
    }

    public function test_channel_and_agent_catalogs(): void
    {
        $this->assertContains(ChannelTypeCatalog::WHATSAPP, ChannelTypeCatalog::all());
        $this->assertContains(ChannelTypeCatalog::DASHBOARD, ChannelTypeCatalog::all());
        $this->assertCount(8, ChannelTypeCatalog::all());
        $this->assertCount(7, AgentTypeCatalog::all());
    }

    public function test_channel_connector_contract_methods(): void
    {
        $methods = array_map(
            static fn (\ReflectionMethod $m): string => $m->getName(),
            (new ReflectionClass(ChannelConnectorInterface::class))->getMethods()
        );
        foreach ([
            'connect', 'disconnect', 'receiveMessage', 'sendMessage',
            'receiveComment', 'replyComment', 'uploadMedia', 'downloadMedia',
            'verifyWebhook', 'syncStatus',
        ] as $required) {
            $this->assertContains($required, $methods);
        }
    }

    public function test_extension_points_and_policies(): void
    {
        $this->assertContains(ExtensionPoints::AGENT, ExtensionPoints::all());
        $this->assertContains(ExtensionPoints::CHANNEL, ExtensionPoints::all());
        $this->assertContains(PolicyCatalog::TENANT_ISOLATION, PolicyCatalog::all());
        $this->assertTrue((new TenantIsolationPolicy())->assertSameTenant('t1', 't1'));
        $this->assertFalse((new TenantIsolationPolicy())->assertSameTenant('t1', 't2'));
    }

    public function test_no_circular_namespace_between_domain_and_registry(): void
    {
        // Domain must not reference Registry; Registry may reference Domain/Contracts.
        $domainFile = file_get_contents(dirname(__DIR__, 2).'/src/Domain/Core/Assistant.php');
        $this->assertIsString($domainFile);
        $this->assertStringNotContainsString('SmartAssistant\\Registry', $domainFile);
    }

    public function test_compat_matrix_lists_ai_core_packages(): void
    {
        $this->assertArrayHasKey('dressnmore/aos-planner', ArchitectureVersion::COMPAT);
        $this->assertArrayHasKey('dressnmore/aos-tools', ArchitectureVersion::COMPAT);
        $this->assertArrayHasKey('dressnmore/aos-response', ArchitectureVersion::COMPAT);
    }
}
