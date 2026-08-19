<?php

declare(strict_types=1);

namespace Tests\Unit\Aos;

use DressnMore\Aos\Core\Module\Contracts\ModuleRegistryInterface;
use DressnMore\Aos\Tools\Application\ToolGateway;
use DressnMore\Aos\Tools\Architecture\ToolsScopeDecision;
use DressnMore\Aos\Tools\Domain\Factories\ToolRequestFactory;
use DressnMore\Aos\Tools\Domain\Result\ExecutionStatus;
use DressnMore\Aos\Tools\Domain\Tool\ToolOperatingMode;
use DressnMore\Aos\Tools\Infrastructure\Handlers\EchoStubToolHandler;
use Tests\TestCase;

final class AosToolsGatewayTest extends TestCase
{
    public function test_tools_module_is_registered(): void
    {
        /** @var ModuleRegistryInterface $registry */
        $registry = $this->app->make(ModuleRegistryInterface::class);
        $this->assertTrue($registry->has('aos.tools'));
    }

    public function test_gateway_registers_and_executes_stub(): void
    {
        /** @var ToolGateway $gateway */
        $gateway = $this->app->make(ToolGateway::class);
        $gateway->register(new EchoStubToolHandler());

        /** @var ToolRequestFactory $factory */
        $factory = $this->app->make(ToolRequestFactory::class);
        $request = $factory->make(
            'EchoStub',
            ['message' => 'from-laravel'],
            ToolOperatingMode::Assistant,
            ['tools.echo.execute'],
            ['tools.echo'],
        );

        $result = $gateway->execute($request);
        $this->assertSame(ExecutionStatus::Success, $result->status());
        $this->assertSame('from-laravel', $result->payload()['echo']);
    }

    public function test_sprint4_scope_excludes_integrations(): void
    {
        $excluded = ToolsScopeDecision::excludedConcerns();
        $this->assertContains('openai', $excluded);
        $this->assertContains('planner', $excluded);
        $this->assertContains('whatsapp', $excluded);
        $this->assertContains('database', $excluded);
        $this->assertSame(['dressnmore/aos-tools'], ToolsScopeDecision::includedPackages());
    }
}
