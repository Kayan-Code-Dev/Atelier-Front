<?php

declare(strict_types=1);

namespace Tests\Unit\Aos;

use DressnMore\Aos\Communication\Architecture\CommunicationScopeDecision;
use DressnMore\Aos\Communication\Contracts\CommunicationHubInterface;
use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use Tests\TestCase;

final class AosCommunicationHubTest extends TestCase
{
    public function test_communication_module_registered(): void
    {
        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        $this->assertTrue($registry->has('aos.communication'));
    }

    public function test_hub_is_bound_and_receives_payload(): void
    {
        /** @var CommunicationHubInterface $hub */
        $hub = $this->app->make(CommunicationHubInterface::class);
        $bag = $hub->receive([
            'channel' => 'web_chat',
            'conversation_id' => 'conv-app',
            'sender' => 'customer',
            'receiver' => 'agent',
            'text' => 'hello from app',
        ]);

        $this->assertSame([], $bag->errors());
        $this->assertTrue($bag->outboundSent());
    }

    public function test_sprint11_scope_excludes_sdk_http_and_database(): void
    {
        $excluded = CommunicationScopeDecision::excludedConcerns();
        $this->assertContains('sdk', $excluded);
        $this->assertContains('http_clients', $excluded);
        $this->assertContains('database', $excluded);
    }
}
