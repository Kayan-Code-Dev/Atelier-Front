<?php

declare(strict_types=1);

namespace DressnMore\Aos\Tools\Domain\Pipeline;

use DressnMore\Aos\Tools\Domain\Request\ToolRequest;
use DressnMore\Aos\Tools\Domain\Resolver\ResolvedTool;
use DressnMore\Aos\Tools\Domain\Result\ToolResult;
use DressnMore\Aos\Tools\Domain\Tool\ToolManifest;
use DressnMore\Aos\Tools\Domain\Tool\ToolMetadata;

/**
 * Mutable state flowing through the Tool Execution Pipeline.
 */
final class PipelineState
{
    private ?ResolvedTool $resolved = null;

    private ?ToolManifest $manifest = null;

    private ?ToolMetadata $metadata = null;

    private ?ToolResult $result = null;

    /** @var list<PipelineStageName> */
    private array $completed = [];

    public function __construct(
        private readonly ToolRequest $request,
    ) {}

    public function request(): ToolRequest
    {
        return $this->request;
    }

    public function mark(PipelineStageName $stage): void
    {
        $this->completed[] = $stage;
    }

    /**
     * @return list<PipelineStageName>
     */
    public function completedStages(): array
    {
        return $this->completed;
    }

    public function setResolved(ResolvedTool $resolved): void
    {
        $this->resolved = $resolved;
        $this->manifest = $resolved->manifest();
        $this->metadata = $resolved->metadata();
    }

    public function resolved(): ?ResolvedTool
    {
        return $this->resolved;
    }

    public function manifest(): ?ToolManifest
    {
        return $this->manifest;
    }

    public function metadata(): ?ToolMetadata
    {
        return $this->metadata;
    }

    public function setResult(ToolResult $result): void
    {
        $this->result = $result;
    }

    public function result(): ?ToolResult
    {
        return $this->result;
    }
}
