<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Application;

use DressnMore\Aos\Events\Contracts\EventBusInterface;
use DressnMore\Aos\Tools\Domain\Contracts\BusinessToolHandlerInterface;
use DressnMore\Aos\Tools\Domain\Discovery\ToolDiscovery;
use DressnMore\Aos\Tools\Domain\Events\ToolAuthorizationRejected;
use DressnMore\Aos\Tools\Domain\Events\ToolExecutionCompleted;
use DressnMore\Aos\Tools\Domain\Events\ToolExecutionFailed;
use DressnMore\Aos\Tools\Domain\Events\ToolExecutionStarted;
use DressnMore\Aos\Tools\Domain\Events\ToolRegistered;
use DressnMore\Aos\Tools\Domain\Events\ToolRequested;
use DressnMore\Aos\Tools\Domain\Events\ToolResolved;
use DressnMore\Aos\Tools\Domain\Events\ToolUnregistered;
use DressnMore\Aos\Tools\Domain\Events\ToolValidationFailed;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineStageName;
use DressnMore\Aos\Tools\Domain\Pipeline\PipelineState;
use DressnMore\Aos\Tools\Domain\Pipeline\ToolExecutionPipeline;
use DressnMore\Aos\Tools\Domain\Registry\ToolRegistry;
use DressnMore\Aos\Tools\Domain\Request\ToolRequest;
use DressnMore\Aos\Tools\Domain\Result\ExecutionStatus;
use DressnMore\Aos\Tools\Domain\Result\ToolResult;
use DressnMore\Aos\Tools\Domain\Tool\ToolIdentifier;

/**
 * Business Tool Gateway — sole entry for invoking registered tools.
 */
final class ToolGateway
{
    public function __construct(
        private readonly ToolRegistry $registry,
        private readonly ToolDiscovery $discovery,
        private readonly ToolExecutionPipeline $pipeline,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function register(BusinessToolHandlerInterface $handler): void
    {
        $this->registry->register($handler);
        $this->eventBus->publish(new ToolRegistered(
            $handler->identifier(),
            $handler->manifest()->version(),
        ));
    }

    public function unregister(ToolIdentifier $identifier): void
    {
        $this->registry->unregister($identifier);
        $this->eventBus->publish(new ToolUnregistered($identifier));
    }

    public function discovery(): ToolDiscovery
    {
        return $this->discovery;
    }

    public function registry(): ToolRegistry
    {
        return $this->registry;
    }

    public function execute(ToolRequest $request): ToolResult
    {
        $this->eventBus->publish(new ToolRequested(
            $request->toolIdentifier(),
            $request->correlationId(),
        ));

        $state = new PipelineState($request);
        $state->mark(PipelineStageName::Requested);

        $this->eventBus->publish(new ToolExecutionStarted(
            $request->toolIdentifier(),
            $request->correlationId(),
        ));

        $this->pipeline->process($state);

        if ($state->manifest() !== null) {
            $this->eventBus->publish(new ToolResolved(
                $request->toolIdentifier(),
                $state->manifest()->version(),
            ));
        }

        $result = $state->result() ?? ToolResult::failed([]);

        if ($result->status() === ExecutionStatus::ValidationFailed) {
            $messages = array_map(
                static fn ($e) => $e->message(),
                $result->errors()
            );
            $this->eventBus->publish(new ToolValidationFailed(
                $request->toolIdentifier(),
                $request->correlationId(),
                $messages,
            ));
        }

        if ($result->status() === ExecutionStatus::Denied) {
            $this->eventBus->publish(new ToolAuthorizationRejected(
                $request->toolIdentifier(),
                $request->correlationId(),
                $result->errors()[0]->message() ?? 'denied',
            ));
        }

        if ($result->isSuccess()) {
            $this->eventBus->publish(new ToolExecutionCompleted(
                $request->toolIdentifier(),
                $request->correlationId(),
                $result->status(),
                $result->executionTimeMs(),
            ));
        } else {
            $reason = $result->errors()[0]->message() ?? $result->status()->value;
            $this->eventBus->publish(new ToolExecutionFailed(
                $request->toolIdentifier(),
                $request->correlationId(),
                $reason,
            ));
            $this->eventBus->publish(new ToolExecutionCompleted(
                $request->toolIdentifier(),
                $request->correlationId(),
                $result->status(),
                $result->executionTimeMs(),
            ));
        }

        return $result;
    }
}
