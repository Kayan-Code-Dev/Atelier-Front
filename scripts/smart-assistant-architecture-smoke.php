<?php

declare(strict_types=1);

/**
 * Smoke: Sprint 21 Smart Assistant Architecture Foundation.
 * Run: php scripts/smart-assistant-architecture-smoke.php
 */

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\SmartAssistant\Architecture\ArchitectureVersion;
use DressnMore\SmartAssistant\Architecture\BoundedContext;
use DressnMore\SmartAssistant\Capability\AgentCapabilityCatalog;
use DressnMore\SmartAssistant\Contracts\Channel\ChannelConnectorInterface;
use DressnMore\SmartAssistant\Contracts\Registry\AgentRegistryInterface;
use DressnMore\SmartAssistant\Contracts\Registry\CapabilityRegistryInterface;
use DressnMore\SmartAssistant\Contracts\Registry\ChannelRegistryInterface;
use DressnMore\SmartAssistant\Domain\Agent\Agent;
use DressnMore\SmartAssistant\Domain\Agent\AgentTypeCatalog;
use DressnMore\SmartAssistant\Domain\Channel\Channel;
use DressnMore\SmartAssistant\Domain\Channel\ChannelTypeCatalog;
use DressnMore\SmartAssistant\Module\SmartAssistantModule;
use DressnMore\SmartAssistant\Registry\DescriptorAgent;
use DressnMore\SmartAssistant\Registry\DescriptorChannel;
use DressnMore\SmartAssistant\Registry\InMemoryAgentRegistry;
use DressnMore\SmartAssistant\Registry\InMemoryCapabilityRegistry;
use DressnMore\SmartAssistant\Registry\InMemoryChannelRegistry;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

require __DIR__.'/../vendor/autoload.php';

$failed = 0;
$assertTrue = static function (bool $cond, string $label) use (&$failed): void {
    echo ($cond ? "  OK  " : " FAIL ").$label."\n";
    if (! $cond) {
        $failed++;
    }
};

echo "Smart Assistant — architecture smoke\n";

$assertTrue(ArchitectureVersion::isFrozen(), 'frozen v1');
$assertTrue(ArchitectureVersion::semver() === '1.0.0', 'semver 1.0.0');
$assertTrue(count(BoundedContext::all()) === 11, '11 bounded contexts');
$assertTrue(count(ChannelTypeCatalog::all()) === 8, '8 channel types');
$assertTrue(count(AgentTypeCatalog::all()) === 7, '7 agent types');
$assertTrue(interface_exists(ChannelConnectorInterface::class), 'channel connector contract');

$agents = new InMemoryAgentRegistry();
$agents->register(new DescriptorAgent(new Agent('agent.sales', 'sales', 'Sales', ['sales.booking'])));
$assertTrue($agents->has('agent.sales'), 'register agent');

$channels = new InMemoryChannelRegistry();
$channels->register(new DescriptorChannel(new Channel('channel.whatsapp', 'whatsapp', 'WhatsApp')));
$assertTrue($channels->has('channel.whatsapp'), 'register channel');

$caps = new InMemoryCapabilityRegistry();
(new AgentCapabilityCatalog())->seed($caps);
$assertTrue(count($caps->forAgentType('sales')) >= 6, 'sales capabilities');
$assertTrue($caps->get('support.faq') !== null, 'register capability');

$agents->register(new DescriptorAgent(new Agent('agent.custom.x', 'custom', 'X', [])));
$assertTrue($agents->has('agent.custom.x'), 'extend agents without core change');

$channels->register(new DescriptorChannel(new Channel('channel.sms', 'sms', 'SMS')));
$assertTrue($channels->has('channel.sms'), 'extend channels without core change');

echo "Smart Assistant — Laravel wiring\n";

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

/** @var ModuleRegistryInterface $modules */
$modules = $app->make(ModuleRegistryInterface::class);
$assertTrue($modules->has('smart.assistant'), 'module registered');

/** @var SmartAssistantModule $module */
$module = $app->make(SmartAssistantModule::class);
$assertTrue($module->version() === '1.0.0', 'module version');
$assertTrue($app->make(AgentRegistryInterface::class) instanceof InMemoryAgentRegistry, 'agent registry bound');
$assertTrue($app->make(ChannelRegistryInterface::class) instanceof InMemoryChannelRegistry, 'channel registry bound');
$assertTrue(count($app->make(CapabilityRegistryInterface::class)->all()) > 0, 'capabilities seeded');

echo $failed === 0 ? "PASSED\n" : "FAILED ({$failed})\n";
exit($failed === 0 ? 0 : 1);
