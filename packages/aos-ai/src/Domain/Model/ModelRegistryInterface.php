<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Model;

interface ModelRegistryInterface
{
    public function register(ModelDescriptor $model): void;

    public function get(ModelId $id): ?ModelDescriptor;

    /** @return list<ModelDescriptor> */
    public function all(): array;

    /** @return list<ModelDescriptor> */
    public function forProvider(string $providerId): array;
}
