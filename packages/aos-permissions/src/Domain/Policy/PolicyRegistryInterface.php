<?php

declare(strict_types=1);

namespace DressnMore\Aos\Permissions\Domain\Policy;

interface PolicyRegistryInterface
{
    public function register(PolicyDefinition $policy): void;

    public function get(PolicyId $id): ?PolicyDefinition;

    /**
     * @return list<PolicyDefinition>
     */
    public function all(): array;

    /**
     * @return list<PolicyDefinition>
     */
    public function byType(PolicyType $type): array;
}
