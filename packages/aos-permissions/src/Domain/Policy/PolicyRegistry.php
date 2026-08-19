<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Policy;

final class PolicyRegistry implements PolicyRegistryInterface
{
    /** @var array<string, PolicyDefinition> */
    private array $items = [];

    public function register(PolicyDefinition $policy): void
    {
        $this->items[$policy->id()->toString()] = $policy;
    }

    public function get(PolicyId $id): ?PolicyDefinition
    {
        return $this->items[$id->toString()] ?? null;
    }

    public function all(): array
    {
        return array_values($this->items);
    }

    public function byType(PolicyType $type): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (PolicyDefinition $p): bool => $p->type() === $type
        ));
    }
}
