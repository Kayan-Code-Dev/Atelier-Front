<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Tests\Unit;

use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Tools\Application\ToolGateway;
use DressnMore\Aos\Tools\Application\ToolPipelineFactory;
use DressnMore\Aos\Tools\Domain\Context\ToolExecutionContext;
use DressnMore\Aos\Tools\Domain\Discovery\ToolDiscovery;
use DressnMore\Aos\Tools\Domain\Executor\ToolExecutor;
use DressnMore\Aos\Tools\Domain\Factories\ToolRequestFactory;
use DressnMore\Aos\Tools\Domain\Registry\ToolRegistry;
use DressnMore\Aos\Tools\Domain\Request\RequestedBy;
use DressnMore\Aos\Tools\Domain\Request\ToolRequest;
use DressnMore\Aos\Tools\Domain\Resolver\ToolResolver;
use DressnMore\Aos\Tools\Domain\Result\ExecutionStatus;
use DressnMore\Aos\Tools\Domain\Result\ToolResult;
use DressnMore\Aos\Tools\Domain\Tool\ToolCategory;
use DressnMore\Aos\Tools\Domain\Tool\ToolCategoryCode;
use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;
use DressnMore\Aos\Tools\Domain\Tool\ToolOperatingMode;
use DressnMore\Aos\Tools\Domain\Validator\ConceptualToolValidator;
use DressnMore\Aos\Tools\Infrastructure\Authorization\CapabilityAuthorizationHook;
use DressnMore\Aos\Tools\Infrastructure\Handlers\EchoStubToolHandler;
use DressnMore\Aos\Tools\Infrastructure\Hooks\InMemoryAnalyticsHook;
use DressnMore\Aos\Tools\Infrastructure\Hooks\InMemoryAuditHook;
use PHPUnit\Framework\TestCase;

final class ToolGatewayEngineTest extends TestCase
{
    private ToolGateway $gateway;

    private ToolRequestFactory $requests;

    protected function setUp(): void
    {
        $registry = new ToolRegistry();
        $discovery = new ToolDiscovery($registry);
        $resolver = new ToolResolver($registry);
        $executor = new ToolExecutor($registry);
        $pipeline = (new ToolPipelineFactory(
            $registry,
            $discovery,
            $resolver,
            new ConceptualToolValidator(),
            new CapabilityAuthorizationHook(),
            $executor,
            new InMemoryAuditHook(),
            new InMemoryAnalyticsHook(),
        ))->create();

        $bus = new class implements EventBusInterface {
            public function publish(object $event): void {}

            public function subscribe(string $event, string|callable $listener): void {}
        };

        $this->gateway = new ToolGateway($registry, $discovery, $pipeline, $bus);
        $this->requests = new ToolRequestFactory();
        $this->gateway->register(new EchoStubToolHandler());
    }

    public function test_tool_registration_and_resolution(): void
    {
        $this->assertTrue($this->gateway->registry()->has(ToolIdentifier::fromString('EchoStub')));
        $manifest = $this->gateway->discovery()->find(ToolIdentifier::fromString('EchoStub'));
        $this->assertNotNull($manifest);
        $this->assertSame('EchoStub', $manifest->identifier()->toString());
    }

    public function test_unknown_tool(): void
    {
        $request = $this->authorizedRequest('UnknownTool', ['message' => 'x']);
        $result = $this->gateway->execute($request);

        $this->assertSame(ExecutionStatus::NotFound, $result->status());
    }

    public function test_immutable_request_and_result(): void
    {
        $request = $this->authorizedRequest('EchoStub', ['message' => 'hello']);
        $this->assertSame('hello', $request->input()['message']);

        $result = ToolResult::success(['ok' => true], 1.5);
        $withRefs = $result->withReferences('a1', 'an1');

        $this->assertNull($result->auditReference());
        $this->assertSame('a1', $withRefs->auditReference());
        $this->assertSame('an1', $withRefs->analyticsReference());
        $this->assertSame(1.5, $result->executionTimeMs());
    }

    public function test_pipeline_execution_success(): void
    {
        $result = $this->gateway->execute($this->authorizedRequest('EchoStub', ['message' => 'ping']));

        $this->assertTrue($result->isSuccess());
        $this->assertSame('ping', $result->payload()['echo']);
        $this->assertNotNull($result->auditReference());
        $this->assertNotNull($result->analyticsReference());
    }

    public function test_validation_failure_missing_input(): void
    {
        $result = $this->gateway->execute($this->authorizedRequest('EchoStub', []));

        $this->assertSame(ExecutionStatus::ValidationFailed, $result->status());
    }

    public function test_authorization_rejected_without_capability(): void
    {
        $context = ToolExecutionContext::create(
            ToolOperatingMode::Assistant,
            ['tools.echo.execute'],
            [], // missing capability
        );
        $request = ToolRequest::create(
            ToolIdentifier::fromString('EchoStub'),
            $context,
            ['message' => 'x'],
            RequestedBy::planner(),
        );

        $result = $this->gateway->execute($request);
        $this->assertSame(ExecutionStatus::Denied, $result->status());
    }

    public function test_execution_context_on_request(): void
    {
        $request = $this->authorizedRequest('EchoStub', ['message' => 'c']);
        $this->assertSame(ToolOperatingMode::Assistant, $request->executionContext()->operatingMode());
        $this->assertTrue($request->executionContext()->hasCapability('tools.echo'));
    }

    public function test_registry_discovery_by_category(): void
    {
        $found = $this->gateway->discovery()->byCategory(
            ToolCategoryCode::fromEnum(ToolCategory::Administration)
        );
        $this->assertCount(1, $found);
    }

    private function authorizedRequest(string $tool, array $input): ToolRequest
    {
        return $this->requests->make(
            $tool,
            $input,
            ToolOperatingMode::Assistant,
            ['tools.echo.execute'],
            ['tools.echo'],
            ['snapshot_version' => 'test'],
            'tenant-1',
            'customer-1',
            'conv-1',
        );
    }
}
