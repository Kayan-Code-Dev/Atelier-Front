<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Context;

use DressnMore\Aos\Workflow\Domain\Variables\WorkflowVariables;

final class WorkflowContext
{
    /**
     * @param array<string, scalar|array|null> $metadata
     */
    public function __construct(
        private readonly string $tenantId,
        private readonly WorkflowVariables $variables,
        private readonly array $metadata = [],
    ) {}

    public function tenantId(): string
    {
        return $this->tenantId;
    }

    public function variables(): WorkflowVariables
    {
        return $this->variables;
    }

    /**
     * @return array<string, scalar|array|null>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }
}
