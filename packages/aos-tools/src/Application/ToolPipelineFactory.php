<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Application;

use DressnMore\Aos\Tools\Domain\Contracts\ToolAnalyticsHookInterface;
use DressnMore\Aos\Tools\Domain\Contracts\ToolAuditHookInterface;
use DressnMore\Aos\Tools\Domain\Contracts\ToolAuthorizationHookInterface;
use DressnMore\Aos\Tools\Domain\Contracts\ToolExecutorInterface;
use DressnMore\Aos\Tools\Domain\Contracts\ToolValidatorInterface;
use DressnMore\Aos\Tools\Domain\Discovery\ToolDiscovery;
use DressnMore\Aos\Tools\Domain\Executor\ToolExecutor;
use DressnMore\Aos\Tools\Domain\Pipeline\Stages\AnalyticsStage;
use DressnMore\Aos\Tools\Domain\Pipeline\Stages\AuditStage;
use DressnMore\Aos\Tools\Domain\Pipeline\Stages\AuthorizationStage;
use DressnMore\Aos\Tools\Domain\Pipeline\Stages\DiscoveryStage;
use DressnMore\Aos\Tools\Domain\Pipeline\Stages\ExecutionContextStage;
use DressnMore\Aos\Tools\Domain\Pipeline\Stages\ExecutionStage;
use DressnMore\Aos\Tools\Domain\Pipeline\Stages\MetadataStage;
use DressnMore\Aos\Tools\Domain\Pipeline\Stages\NormalizationStage;
use DressnMore\Aos\Tools\Domain\Pipeline\Stages\ResolveStage;
use DressnMore\Aos\Tools\Domain\Pipeline\Stages\ValidationStage;
use DressnMore\Aos\Tools\Domain\Pipeline\ToolExecutionPipeline;
use DressnMore\Aos\Tools\Domain\Registry\ToolRegistry;
use DressnMore\Aos\Tools\Domain\Resolver\ToolResolver;

/**
 * Builds the default Tool Execution Pipeline.
 */
final class ToolPipelineFactory
{
    public function __construct(
        private readonly ToolRegistry $registry,
        private readonly ToolDiscovery $discovery,
        private readonly ToolResolver $resolver,
        private readonly ToolValidatorInterface $validator,
        private readonly ToolAuthorizationHookInterface $authorization,
        private readonly ToolExecutorInterface $executor,
        private readonly ToolAuditHookInterface $audit,
        private readonly ToolAnalyticsHookInterface $analytics,
    ) {}

    public function create(): ToolExecutionPipeline
    {
        return new ToolExecutionPipeline([
            new DiscoveryStage($this->discovery),
            new ResolveStage($this->resolver),
            new MetadataStage(),
            new ValidationStage($this->validator),
            new ExecutionContextStage(),
            new AuthorizationStage($this->authorization),
            new ExecutionStage($this->executor),
            new NormalizationStage(),
            new AuditStage($this->audit),
            new AnalyticsStage($this->analytics),
        ]);
    }
}
