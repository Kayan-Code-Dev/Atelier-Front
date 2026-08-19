<?php

declare(strict_types=1);

namespace DressnMore\Aos\Workflow\Domain\Variables;

final class WorkflowVariables
{
    /** @var array<string, array<string, scalar|array|null>> */
    private array $scoped = [];

    public function set(VariableScope $scope, string $key, scalar|array|null $value): void
    {
        $this->scoped[$scope->value][$key] = $value;
    }

    public function get(VariableScope $scope, string $key): scalar|array|null
    {
        return $this->scoped[$scope->value][$key] ?? null;
    }

    /**
     * @return array<string, array<string, scalar|array|null>>
     */
    public function all(): array
    {
        return $this->scoped;
    }
}
