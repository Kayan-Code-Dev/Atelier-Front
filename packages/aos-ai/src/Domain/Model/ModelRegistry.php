<?php

declare(strict_types=1);

namespace DressnMore\Aos\Ai\Domain\Model;

final class ModelRegistry implements ModelRegistryInterface
{
    /** @var array<string, ModelDescriptor> */
    private array $models = [];

    public function register(ModelDescriptor $model): void
    {
        $this->models[$model->id()->toString()] = $model;
    }

    public function get(ModelId $id): ?ModelDescriptor
    {
        return $this->models[$id->toString()] ?? null;
    }

    public function all(): array
    {
        return array_values($this->models);
    }

    public function forProvider(string $providerId): array
    {
        return array_values(array_filter(
            $this->models,
            static fn (ModelDescriptor $m): bool => $m->providerId()->toString() === $providerId
        ));
    }
}
